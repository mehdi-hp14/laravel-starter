<?php

use Kaban\General\Services\LastSettings;
use Kaban\Models\Agency;
use Kaban\Models\Currency;
use Kaban\Models\Limit;


function snakeToCamel($val)
{
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $val)));
}

function optionize($array, $default = null)
{
    $ret = '';
    foreach ($array as $value => $label) {
        if (is_array($label)) {
            $ret .= '<optgroup label="' . $value . '">';
            $ret .= optionize($label, $default);
            $ret .= '</optgroup>';
        } else {
            $ret .= '<option value="' . $value . '"' . (($value === $default) ? ' selected' : '') . '>' . strip_tags($label) . '</option>';
        }
    }

    return $ret;
}


function kaban_path()
{
    return base_path() . '/kaban';
}

function component_path($componentName, $section = 'General')
{
    $path = kaban_path() . '/Components/' . $section . '/' . $componentName;

    if (file_exists($path)) {
        return $path;
    }
    throw new Exception('Component ' . $componentName . ' not found in section ' . $section . ' in address ' . $path, 404);
}

function template_path($templateName = 'kaban')
{
    return kaban_path() . '/Templates/' . $templateName;
}

/**
 * Render a widget with identifier
 *
 * @param string|int $widgetIdentifier widget title or id
 * @return mixed
 */
function renderWidget($widgetIdentifier, ...$args)
{
    try {
        $widget = cacheIt('widgets.identifier.' . $widgetIdentifier, function () use ($widgetIdentifier) {
            return \Kaban\Models\Widget::enabled()->where('id', $widgetIdentifier)->orWhere('title', $widgetIdentifier)->first();
        }, null, 10);

        if (!$widget) {
            return '';
        }
        return (new $widget->widget($widget, ...$args))->render();
    } catch (Exception $exception) {
        Log::error('==-==-==-==');
        Log::error('identifier:' . $widgetIdentifier);
        Log::error($exception->getMessage() . ' ' . $exception->getFile() . '#' . $exception->getLine());
        Log::error('==-==-==-==');
        return '';
    }
}

/**
 * Render widgets from a position.
 *
 * @param $position
 * @return string
 */
function renderPosition($position, ...$args)
{
    try {
        $widgets = /*cacheIt('widgets.position.' . $position, function () use ($position) {
            return*/
            \Kaban\Models\Widget::enabled()->where('position', $position)->orderBy('ordering', 'asc')->get();
//        });

        $rendered = '';
        foreach ($widgets as $widget) {
            $rendered .= (new $widget->widget($widget, ...$args))->render();
        }
        return $rendered;
    } catch (Exception $exception) {
        throw $exception;
        Log::error('==-==-==-==');
        Log::error('position:' . $position);
        Log::error($exception->getMessage() . ' ' . $exception->getFile() . '#' . $exception->getLine());
        Log::error('==-==-==-==');
        return '';
    }
}

/**
 * Render Tour Item.
 *
 * @param $item
 * @param $inactive
 * @return string
 */
function renderTour($item, $inactive = null, $class = null)
{
    return $item->renderListItem($inactive, $class);
}

/**
 * Render comments for a commentable.
 *
 * @param $commentable
 * @return string
 */
function renderComments($commentable)
{
    $commentHtml = renderCommentItems($commentable);
    $modelName = strtolower((new \ReflectionClass($commentable))->getShortName());

    return View::make('KabanTemplates::lastsecond.layouts.parts.comments', compact('commentHtml', 'modelName'))->with('item', $commentable);
}

function renderCommentItems($commentable)
{
    $modelName = strtolower((new \ReflectionClass($commentable))->getShortName());
//    $comments = cacheIt('comments.' . $modelName . '.' . $commentable->id, function () use ($commentable) {
    $comments = $commentable->comments()->enabled()->approved()->direct()->with(['children' => function ($q) {
        $q->enabled()->approved()->with('customer')->withCount('userLike')->orderBy('id', 'ASC');
    }])->with('customer')->withCount('userLike')->orderBy('id', 'DESC')->paginate(\Request::input('comment-limit', 50), ['*'], 'comment-page')/*->withPath(url()->current())*/
    ;
//    });

    return View::make('KabanTemplates::lastsecond.layouts.parts.commentItems', compact('comments', 'modelName'))->with('item', $commentable);
}

function template_name()
{
    return LastSettings::get('template');
}

function template_prefix()
{
    return 'KabanTemplates::' . LastSettings::get('template') . '.';
}


/**
 * Convert Shamsi Date to Miladi Date
 *
 * @param $shamsi
 * @return \Carbon\Carbon|string
 */
function Shamsi2Miladi($shamsi)
{
    if (!isset($shamsi)) {
        return new Carbon\Carbon();
    } else {
        $datetimeArr = explode(' ', $shamsi);

        $dateArr = explode('-', $datetimeArr[0]);

        $miladiDateArr = \jDateTime::toGregorian($dateArr[0], $dateArr[1], $dateArr[2]);

        return $miladiDateArr[0] . '-' . (($miladiDateArr[1] < 10) ? '0' . $miladiDateArr[1] : $miladiDateArr[1]) . '-' . (($miladiDateArr[2] < 10) ? '0' . $miladiDateArr[2] : $miladiDateArr[2]) . (isset($datetimeArr[1]) ? ' ' . $datetimeArr[1] : '');
    }
}

/**
 * Convert Miladi Date to Shamsi Date
 * @param null $miladi
 * @return bool|string
 */
function Miladi2Shamsi($miladi = null)
{
    return \jDate::forge($miladi)->format('Y-m-d H:i:s');
}

/**
 * Wrapper for cache functions
 *
 * @param string $key cache key
 * @param Closure $whatToCache a function to create raw data to be cached
 * @param Closure|null $decorateFromCache a function to decorate raw data (from cache or generated)
 * @param null $duration cache duration
 * @return mixed
 * @throws Exception
 */
function cacheIt($key, Closure $whatToCache, Closure $decorateFromCache = null, $duration = null)
{
    if ($duration !== null) {
        $value = cache()->remember($key, $duration, function () use ($whatToCache) {
            return $whatToCache->call(new dummy());
        });
    } else {
        $value = cache()->rememberForever($key, function () use ($whatToCache) {
            return $whatToCache->call(new dummy());
        });
    }
    if ($decorateFromCache) {
        return $decorateFromCache->call(new dummy(), $value);
    }
    return $value;
}

/**
 * @param $input
 * @return string
 */
function convertCoordinates($input): string
{
    $deg = (int)$input;
    $min_dirty = ($input - $deg) * 60;
    $min = (int)$min_dirty;
    $sec_dirty = ($min_dirty - $min) * 60;
    $sec = round($sec_dirty, 1);

    return $deg . '°' . $min . "'" . $sec . '"';
}

class dummy
{
}

function renderTree($items, $titleDecorator, $linkDecorator, $ulClass = 'menu', $counter = null)
{
    $lastItem = null;
    $level = 1;
    $html = '<ul class="' . $ulClass . '">';
    foreach ($items as $item) {
        if (!$counter || ($counter && $item->$counter->count())) {
            if ($lastItem && $lastItem->depth < $item->depth) {
                $html .= '<ul>';
                $level++;
            } elseif ($lastItem && $lastItem->depth > $item->depth) {
                for ($i = 0; $i < $lastItem->depth - $item->depth; $i++) {
                    $html .= '</ul></li>';
                    $level--;
                }
            } elseif ($lastItem && $lastItem->depth == $item->depth) {
                $html .= '</li>';
            }
            $html .= '<li class="' . $item->link_class . '"><a href="' . $item->{$linkDecorator} . '"' . ($item->target != '' ? ' target="' . $item->target . '"' : '') . '>' . $item->{$titleDecorator} . '</a>';
            $lastItem = $item;
        }
    }
    for ($i = 0; $i < $level; $i++) {
        $html .= '</li></ul>';
    }

    return $html;
}

function renderHotelLocationsTree($items, $titleDecorator, $linkDecorator, $ulClass = 'menu')
{
    $lastItem = null;
    $level = 1;
    $html = '<ul class="' . $ulClass . '">';
    foreach ($items as $item) {
        $hotelsCount = $item->hotels->count();
        if ($hotelsCount) {
            if ($lastItem && $lastItem->depth < $item->depth) {
                $html .= '<ul>';
                $level++;
            } elseif ($lastItem && $lastItem->depth > $item->depth) {
                for ($i = 0; $i < $lastItem->depth - $item->depth; $i++) {
                    $html .= '</ul></li>';
                    $level--;
                }
            } elseif ($lastItem && $lastItem->depth == $item->depth) {
                $html .= '</li>';
            }
            $html .= '<li class="' . $item->link_class . '"><a href="' . $item->{$linkDecorator} . '"' . ($item->target != '' ? ' target="' . $item->target . '"' : '') . '>' . $item->{$titleDecorator} . '</a>';
            $lastItem = $item;
        }
    }
    for ($i = 0; $i < $level; $i++) {
        $html .= '</li></ul>';
    }

    return $html;
}

function percentage($current, $total, $precision = 0)
{
    return round(($current / max(1, $total)) * 100, $precision);
}

function buildQueryString($query, $param, $value)
{
    $query = (array)$query;

    $query[$param] = $value;

    return http_build_query($query);
}

function buildReviewQueryString($reviewQueryString, $param, $value)
{
    return rtrim('?' . buildQueryString($reviewQueryString, $param, $value), '?');
}

// Start : Reviews Section

function slugify($string, $separator = '-', $limit = 8)
{
    $string = strtolower($string);
    $string = str_replace('‌', ' ', $string);
    $string = \Illuminate\Support\Str::words($string, $limit, '');
    $string = mb_ereg_replace('([^آ-ی۰-۹a-z0-9_]|-)+', $separator, $string);
    $string = strtolower($string);
    return trim($string, $separator);
}

function renderReviews($item, $showItems = true)
{
    $reviewFilters = (object)[
        'review_category' => \Request::input('review_category', 0),
        'review_period' => \Request::input('review_period', 0),
    ];

    $categoriesClass = "\\Kaban\\General\\Enums\\E" . class_basename($item) . "ReviewTravelCategory";

    $categoryCounts = getReviewsQuery($item, 0, $reviewFilters->review_period)->select(\DB::raw('travel_category, count(*) as reviews_count'))->groupBy('travel_category')->pluck('reviews_count', 'travel_category')->all();

    $reviewTravelPeriods = collect([0, 1, 3, 6, 12]);

    $reviewTravelCategories = $categoriesClass::transFlip('site.reviews.categories.');

    $averageReviewScore = getReviewsQuery($item, $reviewFilters->review_category, $reviewFilters->review_period)->avg('average_score');

    $reviewScores = getReviewsScores($item, $reviewFilters->review_category, $reviewFilters->review_period);

    $reviews = getReviews($item, $reviewFilters->review_category, $reviewFilters->review_period);

    $reviewsChartScores = getReviewsAverageScores($item, $reviewFilters->review_category, $reviewFilters->review_period);

    $reviewableTypeName = strtolower(class_basename($item));

    return view('KabanTemplates::lastsecond.layouts.parts.reviews', compact('item', 'reviewTravelPeriods', 'reviewTravelCategories', 'reviews', 'reviewScores', 'reviewFilters', 'averageReviewScore', 'reviewableTypeName', 'categoryCounts', 'showItems', 'reviewsChartScores'));
}


function getReviews($item, $category = null, $period = null, $fromApp = false, $loadMedia = true)
{
    $rawReviews = getReviewsQuery($item, $category, $period)
        ->with([
            'parameters',
            'customer' => function ($q) {
                $q->withCount(['reviews']);
            },
        ])
        ->withCount([
            'likes as auth_likes_count' => function ($query) {
                $query->whereNotNull('likes.user_id');
            },
            'likes as guest_likes_count' => function ($query) {
                $query->whereNull('likes.user_id');
            },
        ])
        ->newest()
        ->paginate(20);

    if ($loadMedia) {
        $rawReviews->load([
            'media' => function ($q) {
                $q->whereNotNull('approved_at');
            }
        ]);
    }

    if ($fromApp) {
        return $rawReviews->setCollection($rawReviews->getCollection()->transform(function ($item) {
            return $item->appDetails;
        }));
    } else {
        return $rawReviews;
    }
}

function getReviewsQuery($item, $category, $period)
{
    return $item->reviews()->enabled()->status(\Kaban\General\Enums\EReviewStatus::approved)
        ->when($category, function ($q) use ($category) {
            $q->where('travel_category', $category);
        })
        ->when($period, function ($q) use ($period) {
            $q->recentMonths($period);
        });
}

function getReviewsScores($item, $category, $period)
{
    $items = \Kaban\Models\Parameter::select(\DB::raw('`parameters`.`id`, `parameters`.`title`, avg(`parameter_review`.`score`) as avg_score'))
        ->leftJoin('parameter_review', 'parameters.id', '=', 'parameter_review.parameter_id')
        ->leftJoin('reviews', function ($join) {
            $join->on('parameter_review.review_id', '=', 'reviews.id');
        })
        ->where('type', $item->reviewable_type)
        //->whereIn('parameters.id', $item->allowed_review_parameters->pluck('id')->all())
        ->where('reviews.reviewable_id', $item->id)
        ->where('reviews.reviewable_type', get_class($item))
        ->where('reviews.state', \Kaban\Core\Enums\EState::enabled)
        ->where('reviews.status', \Kaban\General\Enums\EReviewStatus::approved)
        ->when($category, function ($q) use ($category) {
            $q->where('reviews.travel_category', $category);
        })
        ->when($period, function ($q) use ($period) {
            $time = getAllowedReviewPeriodTime($period);

            $q->where(function ($q) use ($time) {
                $q->where(function ($query) use ($time) {
                    $query->where('reviews.travel_year', '>', $time->format('Y'));
                })
                    ->orWhere(function ($query) use ($time) {
                        $query->where('reviews.travel_year', '=', $time->format('Y'))->where('reviews.travel_period', '>=', (int)$time->format('m'));
                    });
            });
        })
        ->groupBy('parameters.id', 'parameters.title')->pluck('avg_score', 'title')->all();

    if (count($items) == 0) {
        $items = $item->allowed_review_parameters->mapWithKeys(function ($item, $key) {
            return [
                $item->title => 0,
            ];
        });
    }
    return $items;
}

function getReviewsAverageScores($item, $category, $period)
{
    $chartCount = cacheIt('reviews.statistics.item-' . $item->id . '.category-' . $category . '.period-' . $period, function () use ($item, $category, $period) {
        return $item->reviews()->valid()
            ->select(\DB::raw('SUM(IF(`average_score` >= 4.5, 1, 0)) AS excellent_count'))
            ->addSelect(\DB::raw('SUM(IF(`average_score` < 4.5 AND `average_score` >= 3.5, 1, 0)) AS good_count'))
            ->addSelect(\DB::raw('SUM(IF(`average_score` < 3.5 AND `average_score` >= 2.5, 1, 0)) AS medium_count'))
            ->addSelect(\DB::raw('SUM(IF(`average_score` < 2.5 AND `average_score` >= 1.5, 1, 0)) AS weak_count'))
            ->addSelect(\DB::raw('SUM(IF(`average_score` < 1.5, 1, 0)) AS awful_count'))
            ->when($category, function ($q) use ($category) {
                $q->where('travel_category', $category);
            })
            ->when($period, function ($q) use ($period) {
                $q->recentMonths($period);
            })
            ->first()
            ->toArray();
    });

    return $chartCount;
}

function getAllowedReviewPeriodTime($month)
{
    return \jDate::forge("now - {$month} months");
}

function getAllowedServicesCount($agency_id, $service_id)
{
    //TODO check if the better way, implement that
    $agency = Agency::enabled()->findOrFail($agency_id);
    $used = $agency->services()->where('service_id', $service_id)->count();
    $limit = $agency->limits()->where('service_id', $service_id)->first();

    return ($limit->count > $used);
}

// End : Reviews Section

/*function decoratePrices($priceList)
{
    $currencies = cacheIt('currencies', function () {
        return Currency::get();
    }, function ($currencies) {
        return $currencies->pluck('title_fa', 'id')->all();
    });
    $rendered = '';
    $i = 0;
    foreach ($priceList as $currencyId => $cost) {
        if (isset($currencies[$currencyId]) && $cost != 0) {
            if ($i > 0) {
                $rendered .= '<span class="clearfix"></span><span class="plus">+</span>';
            }
            $rendered .= '<label class="green-text" >' . number_format($cost, 0) . '</label><label>' . $currencies[$currencyId] . '</label>';
        }
        $i++;
    }

    return $rendered;}*/

function renderAlbumCategories($item)
{
    $imageCategoriesClass = $item->getReviewImageCategoriesClass();

    $imageCategories = $imageCategoriesClass::transFlip('site.albums.media.category_');


    /*$images = $item->media()->select(['*', DB::raw('(JSON_EXTRACT(data, \'$.category_id\')) as milad')])->wherePivot('album', 'customer')->groupBy('milad')->get()->mapWithKeys(function ($media, $key) {*/
    $images = $item->media()->valid()->select(['*', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(data, \'$.category_id\')) as milad')])->wherePivotIn('album', ['customer', 'reviews'])->groupBy('milad')->get()->mapWithKeys(function ($media, $key) {
        $data = json_decode($media->pivot->data);
        return [$data->category_id => $media->url];
    })->toArray();

    return view('KabanTemplates::lastsecond.layouts.parts.album-categories', compact('item', 'imageCategories', 'images'));
}

function renderAlbumImages($item)
{
    $imageCategoriesClass = $item->getReviewImageCategoriesClass();

    $imageCategories = $imageCategoriesClass::transFlip('site.albums.media.category_');

    $images = $item->media()->valid()
        ->select(['*', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(data, \'$.category_id\')) as image_category_id')])
        ->wherePivotIn('album', ['customer', 'reviews'])
        ->take(5)
        ->get();

    return view('KabanTemplates::lastsecond.layouts.parts.album-images', compact('item', 'imageCategories', 'images'));
}

function decorateDateToArray($date)
{
    $dateArr = explode('-', explode(' ', $date)[0]);

    $months = [
        'فروردین',
        'اردیبهشت',
        'خرداد',
        'تیر',
        'مرداد',
        'شهریور',
        'مهر',
        'آبان',
        'آذر',
        'دی',
        'بهمن',
        'اسفند',
    ];
    return [
        'day' => $dateArr[2],
        'month' => $dateArr[1],
        'year' => $dateArr[0],
        'month_name' => $months[(int)$dateArr[1] - 1]
    ];
}

function decoratejDateToArray($date)
{
    $dateArr = explode('-', explode(' ', $date)[0]);

    $months = [
        'فروردین',
        'اردیبهشت',
        'خرداد',
        'تیر',
        'مرداد',
        'شهریور',
        'مهر',
        'آبان',
        'آذر',
        'دی',
        'بهمن',
        'اسفند',
    ];

    $year = (string)$dateArr[0];
    if (strlen($dateArr[0]) === 4) {
        $year = $dateArr[0][2] . $dateArr[0][3];
    } elseif (strlen($dateArr[0]) === 2) {
        $year = $dateArr[0][0] . $dateArr[0][1];
    }

    return [
        'day' => $dateArr[2],
        'month' => $dateArr[1],
        'full_year' => $dateArr[0],
        'year' => $year,
        'month_name' => $months[(int)$dateArr[1] - 1],
        'style_1' => $dateArr[2] . ' ' . $months[(int)$dateArr[1] - 1] . ' ' . $year,
        'style_2' => $dateArr[2] . ' ' . $months[(int)$dateArr[1] - 1] . ' ' . (string)$dateArr[0],
        'style_3' => $dateArr[2] . ' ' . $dateArr[1] . ' ' . $year,
        'style_4' => $dateArr[2] . ' ' . $dateArr[1] . ' ' . (string)$dateArr[0],
    ];
}

function sanitizePrices($priceList)
{
    $currencies = cacheIt('currencies', function () {
        return Currency::get();
    }, function ($currencies) {
        return $currencies->pluck('title_fa', 'id')->all();
    });

    $priceArr = [];

    foreach ($priceList as $currencyId => $value) {
        if (isset($currencies[$currencyId]) && $value > 0) {
            $priceArr[] = [
                'currency_name' => $currencies[$currencyId],
                'value' => $value
            ];
        }
    }
    return $priceArr;
}

/**
 * @param \Illuminate\Http\Request|null $request
 * @return array|null|string
 */
function getIP($request = null)
{
    if (!$request) {
        return Request::hasHeader('ar-real-ip') ? Request::header('ar-real-ip') : Request::getClientIp();
    }
    return $request->hasHeader('ar-real-ip') ? $request->header('ar-real-ip') : $request->getClientIp();
}

function generateLocationIds($location_ids)
{
    if (is_string($location_ids)) {
        $location_ids = [$location_ids];
    }

    $all_location_ids = \Kaban\Models\Location::whereIn('id', $location_ids)->enabled()->get()->map(function ($item, $key) {
        return $item->ancestorsAndSelf()->enabled()->get();
    })->collapse()->unique()->pluck('id')->toArray();

    return '-' . implode('-', $all_location_ids) . '-';
}

function generateChildrenLocationIds($location_ids)
{
    if (is_string($location_ids)) {
        $location_ids = [$location_ids];
    }

    $parentLocations = \Kaban\Models\Location::whereIn('id', $location_ids)->enabled()->get();

    $query = \Kaban\Models\Location::query()->enabled();
    $query->where(function ($query) use ($parentLocations) {
        foreach ($parentLocations as $parentLocation) {
            $query->orWhere(function ($query) use ($parentLocation) {
                $query->where('lft', '>=', $parentLocation->lft)
                    ->where('rgt', '<=', $parentLocation->rgt);
            });
        }
    });
    $all_location_ids = $query->pluck('id')->toArray();

    return $all_location_ids;
}

function RoundThousand($value, $kSymbol = 'K')
{
    $valueByThousand = $value / 1000;
    if ($valueByThousand < 1) {
        return $value;
    }
    $residual = $value % 1000;
    if ($residual < 100) {
        $precision = 0;
    } else {
        $precision = 1;
    }
    return round($valueByThousand, $precision) . $kSymbol;
}

function json_safe($input)
{
    return trim(json_encode($input), '"');
}

function hashtagRegex()
{
    $str = 'A-Za-zآ-ی';

    return '/#([' . $str . '0-9_\x{200C}]+\b)/u';
}


function prepare($content)
{
    $preparedContent = replaceHashtags($content);

    return $preparedContent;
}

/**
 * @param $content
 * @return null|string|string[]
 */
function replaceHashtags($content)
{
    $english = preg_replace_callback(
        hashtagRegex(),
        function ($matches) {
            if (strlen($matches[1]) > 0) {
                return '<a class="hashtag ltr" href="/t/' . $matches[1] . '">' . $matches[0] . '</a>';
            }
            return '#' . $matches[1];
        },
        strip_tags($content)
    );

    return preg_replace_callback(
        hashtagRegex(true),
        function ($matches) {
            if (strlen($matches[1]) > 0) {
                return '<a class="hashtag" href="/t/' . $matches[1] . '">' . $matches[0] . '</a>';
            }
            return '#' . $matches[1];
        },
        $english
    );
}

/**
 * @param $content
 * @return null|string|string[]
 */
function matchHashtags($content)
{
    preg_match_all(hashtagRegex(), $content, $matches);

    if (isset($matches[1])) {
        return $matches[1];
    }

    return '';
}

function serverDetector()
{
    $ip = getIP();
    if ($ip == '31.47.32.230') {
        $server_ip = $_SERVER['SERVER_ADDR'];
        switch ($server_ip) {
            case '79.175.169.179':
                $class = 'firstServer';
                break;
            case '79.175.171.218':
                $class = 'secondServer';
                break;
            default:
                $class = 'unknownServer';
        }
        return '<i class="' . $class . '"></i>';
    }
    return '';
}

function convertSecondsToMinutes($seconds)
{
    $min = (int)floor($seconds / 60);
    $sec = $seconds % 60;

    return $min . ':' . $sec;
}

function makeExternalLink($link)
{
    if (strpos($link, 'http') === 0) {
        return $link;
    }

    return 'http://' . $link;
}

function alphabet()
{
    return array_flip([
        'آ',
        'ا',
        'ب',
        'پ',
        'ت',
        'ث',
        'ج',
        'چ',
        'ح',
        'خ',
        'د',
        'ذ',
        'ر',
        'ز',
        'ژ',
        'س',
        'ش',
        'ص',
        'ض',
        'ط',
        'ظ',
        'ع',
        'غ',
        'ف',
        'ق',
        'ک',
        'گ',
        'ل',
        'م',
        'ن',
        'و',
        'ه',
        'ی',
        'ئ',
    ]);
}

function cleanHtmlChars($value)
{
    return htmlspecialchars($value);
}

function decorateTripDuration($duration)
{
    if (floor($duration) == $duration) {
        return ((int)$duration) . ' روز';
    } else {
        return ((int)$duration) . ' روز و نیم';
    }
}

function dataVersion()
{
    if (config('lastsecond.DATA_VERSION') && config('lastsecond.DATA_VERSION') != '') {
        return config('lastsecond.DATA_VERSION');
    } else {
        $now = new \Carbon\Carbon();
        return $now->format('YmdH');
        return $now->format('Ymd') . floor(((int)$now->format('H')) / 1);
    }
}


function getUserPanelName()
{
    if (Auth::check() && $role = Auth::user()->role) {
        if (isset($role)) {
            return $role->panel;
        }
    }

    return null;
}

function imageSize($url, $width, $height = null)
{
    $array = pathinfo($url);

    return $array['dirname'] . '/' . $array['filename'] . '--' . $width . 'x' . $height . '.' . $array['extension'];
}

function mobile_captcha()
{
    $data = [
        'lazyload' => true,
    ];

    return \Kaban\General\Services\Captcha::generate($data, 'mobile.partials.captcha');
}

function desktop_captcha()
{
    $data = [
        'lazyload' => true,
    ];

    return \Kaban\General\Services\Captcha::generate($data, 'desktop.partials.captcha');
}

function render_hotel_stars($grade)
{
    return view('KabanTemplates::lastsecond.mobile.partials.hotel-grade', compact('grade'));
}

function render_review_bullets($score)
{
    return view('KabanTemplates::lastsecond.mobile.partials.review-bullets', compact('score'));
}

function mobile_render_review_details($reviewable, $categoryFilter = 0, $periodFilter = 0, $showReviews = false)
{
    $categoriesClass = $reviewable->getReviewTravelCategoriesClass();

    $categoryCounts = getReviewsQuery($reviewable, 0, $periodFilter)
        ->select(\DB::raw('travel_category, count(*) as reviews_count'))
        ->groupBy('travel_category')
        ->pluck('reviews_count', 'travel_category')
        ->all();

    $travelCategories = $categoriesClass::transFlip('site.reviews.categories.');

    $averageReviewScore = getReviewsQuery($reviewable, $categoryFilter, $periodFilter)->avg('average_score');

    $reviewScores = getReviewsScores($reviewable, $categoryFilter, $periodFilter);

    $totalReviews = array_sum($categoryCounts);

    $reviews = getReviews($reviewable, $categoryFilter, $periodFilter, false, false);

    return view('KabanTemplates::lastsecond.mobile.partials.reviews', compact('reviewable', 'reviews', 'travelCategories', 'categoryCounts', 'categoryFilter', 'periodFilter', 'averageReviewScore', 'reviewScores', 'totalReviews'));
}

function mobile_render_review_items($reviews)
{
    return view('KabanTemplates::lastsecond.mobile.partials.review-items', compact('reviews'));
}


function get_currencies()
{
    return cacheIt('currencies', function () {
        return Currency::query()->get();
    }, function ($currencies) {
        return $currencies->pluck('title_fa', 'id')->all();
    });
}

function get_template_view($view)
{
    return 'KabanTemplates::' . LastSettings::get('template') . '.' . $view;
}

function share_link($link, $provider = 'link')
{
    switch ($provider) {
        case 'telegram':
            return "https://telegram.me/share/url?url=" . $link;
        case 'whatsapp':
            return "https://api.whatsapp.com/send?text=" . $link;
        case "link":
        default:
            return $link;
    }
}

function toPersian($string)
{
    // Reverse the string
    $len = mb_strlen($string, 'utf-8');
    $result = '';
    for ($i = ($len - 1); $i >= 0; $i--) {
        $result .= mb_substr($string, $i, 1, 'utf-8');
    }
    // These chars work as space when a character comes after them, so the next character will not connect to them
    $spaces_after = array('', ' ', 'ا', 'آ', 'أ', 'إ', 'د', 'ذ', 'ر', 'ز', 'ژ', 'و', 'ؤ');
    // These chars work as space when a character comes before them, so the previous character will not connect to them
    $spaces_before = array('', ' ');
    // Persian chars with their different styles at different positions:
    // Alone, After a non-space char, Before a non-space char, between two non-space chars
    $chars = array();
    $chars[] = array('آ', 'ﺂ', 'آ', 'ﺂ');
    $chars[] = array('أ', 'ﺄ', 'ﺃ', 'ﺄ');
    $chars[] = array('إ', 'ﺈ', 'ﺇ', 'ﺈ');
    $chars[] = array('ا', 'ﺎ', 'ا', 'ﺎ');
    $chars[] = array('ب', 'ﺐ', 'ﺑ', 'ﺒ');
    $chars[] = array('پ', 'ﭗ', 'ﭘ', 'ﭙ');
    $chars[] = array('ت', 'ﺖ', 'ﺗ', 'ﺘ');
    $chars[] = array('ث', 'ﺚ', 'ﺛ', 'ﺜ');
    $chars[] = array('ج', 'ﺞ', 'ﺟ', 'ﺠ');
    $chars[] = array('چ', 'ﭻ', 'ﭼ', 'ﭽ');
    $chars[] = array('ح', 'ﺢ', 'ﺣ', 'ﺤ');
    $chars[] = array('خ', 'ﺦ', 'ﺧ', 'ﺨ');
    $chars[] = array('د', 'ﺪ', 'ﺩ', 'ﺪ');
    $chars[] = array('ذ', 'ﺬ', 'ﺫ', 'ﺬ');
    $chars[] = array('ر', 'ﺮ', 'ﺭ', 'ﺮ');
    $chars[] = array('ز', 'ﺰ', 'ﺯ', 'ﺰ');
    $chars[] = array('ژ', 'ﮋ', 'ﮊ', 'ﮋ');
    $chars[] = array('س', 'ﺲ', 'ﺳ', 'ﺴ');
    $chars[] = array('ش', 'ﺶ', 'ﺷ', 'ﺸ');
    $chars[] = array('ص', 'ﺺ', 'ﺻ', 'ﺼ');
    $chars[] = array('ض', 'ﺾ', 'ﺿ', 'ﻀ');
    $chars[] = array('ط', 'ﻂ', 'ﻃ', 'ﻄ');
    $chars[] = array('ظ', 'ﻆ', 'ﻇ', 'ﻈ');
    $chars[] = array('ع', 'ﻊ', 'ﻋ', 'ﻌ');
    $chars[] = array('غ', 'ﻎ', 'ﻏ', 'ﻐ');
    $chars[] = array('ف', 'ﻒ', 'ﻓ', 'ﻔ');
    $chars[] = array('ق', 'ﻖ', 'ﻗ', 'ﻘ');
    $chars[] = array('ک', 'ﻚ', 'ﻛ', 'ﻜ');
    $chars[] = array('ك', 'ﻚ', 'ﻛ', 'ﻜ');
    $chars[] = array('گ', 'ﮓ', 'ﮔ', 'ﮕ');
    $chars[] = array('ل', 'ﻞ', 'ﻟ', 'ﻠ');
    $chars[] = array('م', 'ﻢ', 'ﻣ', 'ﻤ');
    $chars[] = array('ن', 'ﻦ', 'ﻧ', 'ﻨ');
    $chars[] = array('و', 'ﻮ', 'ﻭ', 'ﻮ');
    $chars[] = array('ؤ', 'ﺆ', 'ﺅ', 'ﺆ');
    $chars[] = array('ی', 'ﯽ', 'ﯾ', 'ﯿ');
    $chars[] = array('ي', 'ﻲ', 'ﻳ', 'ﻴ');
    $chars[] = array('ئ', 'ﺊ', 'ﺋ', 'ﺌ');
    $chars[] = array('ه', 'ﻪ', 'ﮬ', 'ﮭ');
    $chars[] = array('ۀ', 'ﮥ', 'ﮬ', 'ﮭ');
    $chars[] = array('ة', 'ﺔ', 'ﺗ', 'ﺘ');
    $chars[] = array(' ', ' ', ' ', ' ');
    $chars[] = array('0', '0', '0', '0');
    $chars[] = array('1', '1', '1', '1');
    $chars[] = array('2', '2', '2', '2');
    $chars[] = array('3', '3', '3', '3');
    $chars[] = array('4', '4', '4', '4');
    $chars[] = array('5', '5', '5', '5');
    $chars[] = array('6', '6', '6', '6');
    $chars[] = array('7', '7', '7', '7');
    $chars[] = array('8', '8', '8', '8');
    $chars[] = array('9', '9', '9', '9');
    // Start processing the reversed string
    $string = $result;
    $len = mb_strlen($string, 'utf-8');
    $result = '';
    for ($i = 0; $i < $len; $i++) {
        $previous_char = $i > 0 ? mb_substr($string, $i - 1, 1, 'utf-8') : '';
        $current_char = mb_substr($string, $i, 1, 'utf-8');
        $next_char = $i < ($len - 1) ? mb_substr($string, $i + 1, 1, 'utf-8') : '';
        foreach ($chars as $char) {
            if (in_array($current_char, $char)) {
                if (!in_array($next_char, $spaces_after) && !in_array($previous_char, $spaces_before)) {
                    $result .= $char[3];
                } elseif (!in_array($previous_char, $spaces_before)) {
                    $result .= $char[2];
                } elseif (!in_array($next_char, $spaces_after)) {
                    $result .= $char[1];
                } else {
                    $result .= $char[0];
                }
            }
        }
    }
    return $result;
}

function maskMobile($mobile)
{
    if ($mobile) {
        $mobile = farsi2EnglishNums($mobile);
        return substr($mobile, 0, -7) . 'xxxx' . substr($mobile, -3);
    }
    return 'xxxx';
}

/**
 * @param $input
 * @return mixed
 */
function farsi2EnglishNums($input)
{
    return str_replace([
        '۰',
        '۱',
        '۲',
        '۳',
        '۴',
        '۵',
        '۶',
        '۷',
        '۸',
        '۹',
    ], [
        '0',
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
    ], $input);
}


function mobile_render_album_details($item, $categoryFilter = null)
{
    $showCategories = $item instanceof Kaban\Models\Hotel;

    $totalImages = 0;
    $categoryCounts = 1;
    if ($showCategories) {
        $imageCategoriesClass = $item->getReviewImageCategoriesClass();

        $categoryCounts = $item->media()->valid()
            ->select([DB::raw('JSON_UNQUOTE(JSON_EXTRACT(data, \'$.category_id\')) as category_id')])
            ->addSelect(\DB::raw('COUNT(distinct media.id) as media_count'))
            ->wherePivotIn('album', ['customer', 'reviews'])
            ->groupBy('category_id')
            ->get()
            ->map(function ($result) use ($imageCategoriesClass) {
                return [
                    'category_id' => $result->category_id,
                    'category_title' => trans('site.albums.media.category_' . $imageCategoriesClass::search((int)$result->category_id)),
                    'media_count' => $result->media_count,
                ];
            });

        $totalImages = $categoryCounts->sum('media_count');
    }

    $query = $item->media()->valid()
        ->select('media.*')
        ->addSelect(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(data, \'$.category_id\')) as category_id'))
        ->whereNotNull('approved_at');

    if ($categoryFilter) {
        $pattern = '"category_id":"' . $categoryFilter . '"';

        $query->wherePivot('data', 'like', "%{$pattern}%");
    }

    $images = $query->wherePivotIn('album', ['customer', 'reviews'])
        //->with('customer')
        ->orderBy('media.id', 'desc')
        ->paginate(15);

    $totalImages = $totalImages ?: $images->total();

    return view('KabanTemplates::lastsecond.mobile.partials.album-details', compact('item', 'showCategories', 'categoryCounts', 'images', 'categoryFilter', 'totalImages'));
}

function mobile_render_album_items($items)
{
    return view('KabanTemplates::lastsecond.mobile.partials.album-items', compact('items'));
}


function build_map_data($data)
{
    $cryptor = new \Kaban\General\Helpers\Cryptor();

    $string = '';

    foreach ($data as $name => $valueArr) {
        $string .= 'var ' . $name . ' = ' . ($valueArr['type'] == 'string' ? '"' : '') . $valueArr['value'] . ($valueArr['type'] == 'string' ? '"' : '') . "\n";
    }

    return $cryptor->encrypt($string);
}

function build_map_file($file)
{
    $cryptor = new \Kaban\General\Helpers\Cryptor();

    return $cryptor->encrypt($file);
}

function parse_jdate($value, $format = 'Y-m-d H:i:s')
{
    $date = jDateTime::createDateTimeFromFormat($format, $value);

    return jdate($date);
}


function unprocessMentions($content)
{
    return preg_replace('/(\[(\@[A-Za-z0-9\.\_]*?)\:[0-9]*?\])/', '$2', $content);
}

/**
 * @param string $token [default null]
 * @param bool $force [default false]
 * @return \Kaban\Models\User
 */
function get_api_user($token = null, $force = false)
{
    try {
        if (!$token) {
            if (config('app.env') == 'testing') {
                \Tymon\JWTAuth\Facades\JWTAuth::setRequest(request());
            }
            $token = \Tymon\JWTAuth\Facades\JWTAuth::getToken();

            if (!$token) {
                $token = \Request::input('token', null);
            }
        }

        if (!$token) {
            return null;
        }

        if ($force) {
            cache()->store('array')->forget('jwt.tokens.' . $token);
        }

        return cache()->store('array')->remember('jwt.tokens.' . $token, 2, function () use ($token) {
            $user = \Tymon\JWTAuth\Facades\JWTAuth::toUser($token);

            if ($user->state != Kaban\Core\Enums\EState::enabled) {
                \Tymon\JWTAuth\Facades\JWTAuth::invalidate($token);
                return null;
            }

            $user->load('lastgramer');

            return $user;
        });

    } catch (\Exception $e) {
        return null;
    }
}

/**
 * Remove the LEFT-to-RIGHT and RIGHT-to-LEFT marks and &nbsp;
 *
 * @param string $str
 * @return string
 */
function strip_marks($str)
{
    return preg_replace('/(\x{200e}|\x{200f}|&nbsp;)/u', '', $str);
}

function mask($input, $type = 'email')
{
    switch ($type) {
        case 'email':
            return substr($input, 0, 2) . '*****' . substr($input, strpos($input, '@'));
        case 'mobile':
            return substr($input, 0, 4) . '***' . substr($input, -4);
    }

    return $input;
}

function getAuthRoute($link = null, $nextUrl = null)
{
    $params = ['link' => $link ?? 'login'];
    if ($nextUrl !== false) {
        $params['nextUrl'] = $nextUrl ?? URL::current();
    }
    return route('general.auth.auth.form', $params);
}
function d($data){
    if(is_null($data)){
        $str = "<i>NULL</i>";
    }elseif($data == ""){
        $str = "<i>Empty</i>";
    }elseif(is_array($data)){
        if(count($data) == 0){
            $str = "<i>Empty array.</i>";
        }else{
            $str = "<table style=\"border-bottom:0px solid #000;\" cellpadding=\"0\" cellspacing=\"0\">";
            foreach ($data as $key => $value) {
                $str .= "<tr><td style=\"background-color:#008B8B; color:#FFF;border:1px solid #000;\">" . $key . "</td><td style=\"border:1px solid #000;\">" . d($value) . "</td></tr>";
            }
            $str .= "</table>";
        }
    }elseif(is_resource($data)){
        while($arr = mysql_fetch_array($data)){
            $data_array[] = $arr;
        }
        $str = d($data_array);
    }elseif(is_object($data)){
        $str = d(get_object_vars($data));
    }elseif(is_bool($data)){
        $str = "<i>" . ($data ? "True" : "False") . "</i>";
    }else{
        $str = $data;
        $str = preg_replace("/\n/", "<br>\n", $str);
    }
    return $str;
}
