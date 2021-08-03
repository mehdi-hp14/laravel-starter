<?php

namespace Kaban\Models;


use Illuminate\Database\Eloquent\Model;
use Kaban\Core\Enums\EState;
use Kaban\Core\Models\BaseModel;
use Kaban\General\Enums\EAlbumStatus;
use Kaban\General\Interfaces\IIgnorable;

class Media extends Model {

    protected $fillable = [
        'name',
        'title',
        'url',
        'disk',
        'path',
        'type',
        'user_id',
        'created_by',
        'updated_by',
        'status',
        'mime_type',
        'approved_at',
        'description',
        'old_id',
        'ordering',
    ];

    protected $appends = [
        'is_approved',
    ];

    /**
     * Country constructor.
     *
     * @param array $attributes
     */
    public function __construct( array $attributes = [] ) {
        parent::__construct( $attributes );
    }

    public static function table() {
        return ( new self )->getTable();
    }


    public function tags() {
        return $this->morphToMany( Tag::class, 'taggable' );
    }


    /**
     * Media related customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo( User::class, 'user_id', 'id', 'customer' );
    }

    /**
     * Media url accessor
     *
     * @return string
     */
//    public function getUrlAttribute() {
//        return Str::startsWith( $this->attributes['url'], [
//            'http://',
//            'https://'
//        ] ) ? $this->attributes['url'] : 'http://' . $this->attributes['url'];
//    }

    /**
     * Media thumbnail url accessor
     *
     * @return string
     */
//    public function getThumbnailUrlAttribute()
//    {
//        $path = rtrim(config('lastsecond.UPLOAD_HOST', 'http://cdn.last.upload.ir'), '/');
//        $path = starts_with($path, ['http://', 'https://']) ? $path : 'http://' . $path;
//
//        return $path . '/' . rtrim(config('lastsecond.UPLOAD_THUMBNAIL_PATH', 'thubmnails'), '/') . '/' . $this->title;
//    }

    /**
     * Media thumbnail path accessor
     *
     * @return string
     */
//    public function getThumbnailFullPathAttribute()
//    {
//        return rtrim(config('lastsecond.UPLOAD_THUMBNAIL_PATH', 'thubmnails'), '/') . '/' . $this->title;
//    }

    /**
     * Media thumbnail path accessor
     *
     * @return string
     */
    public function getFullPathAttribute() {
        return $this->path . '/' . $this->name;
    }

    /**
     * Image approvement check accessor
     *
     * @return bool
     */
    public function getIsApprovedAttribute() {
        return ! is_null( $this->approved_at );
    }

    public function getApprovementStatusAttribute() {
        return is_null( $this->approved_at ) ? trans( 'admin.review.reviews.is_not_approved' ) : trans( 'admin.review.reviews.is_approved' );
    }

    /**
     * Media thumbnail path accessor
     *
     * @return string
     */
//    public function getThumbnailPathAttribute()
//    {
//        return rtrim(config('lastsecond.UPLOAD_THUMBNAIL_PATH', 'thubmnails'), '/') . '/' . $this->title;
//    }


    public function getDetailsAttribute() {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'title'         => $this->title,
            'description'   => $this->description,
            'url'           => $this->url,
            'path'          => $this->path,
            'mime_type'     => $this->mime_type,
            'thumbnail_url' => $this->thumbnail_url,
            'created_at'    => $this->created_at,
            'is_approved'   => $this->is_approved,
        ];
    }

    public function getImageDecorateAttribute() {
        if ( $this->path ) {
            $html = '<a class="btn purple btn-outline btn-sm btn-circle" data-toggle="modal" href="#image' . $this->id . '"> مشاهده تصویر </a>';
            $html .= '<div id="image' . $this->id . '" class="modal fade" tabindex="-1" aria-hidden="true"> <div class="modal-dialog"> <div class="modal-content"> <div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button> </div> <div class="modal-body"><div class="row"> <img class="col-md-12" id="image' . $this->id . '" src="' . $this->url . '" style="max-width: 100%"></div> </div> </div> </div> </div>';

            return $html;
        }

        return '';
    }

    public function getUserNameAttribute() {
        return $this->user ? $this->user->full_name : '';
    }

    public function getJalaliApprovedAtAttribute() {
        return $this->prepareGetDateAttribute( $this->approved_at );
    }

    public function scopeValid( $query ) {
        $query->where( 'state', EState::enabled )
              ->whereNotNull( 'approved_at' );
    }

    public function mediable() {
        $class = $this->mediable_type;

        $mediable = $class ? app( $class )::find( $this->mediable_id ) : null;

        $this->setRelation( 'mediable', $mediable );

        return $mediable;
    }

    public function getMediableTitleAttribute() {
        $mediable = $this->mediable();

        return $mediable ? $mediable->reviewable_title : null;
    }

    public function getApprovalStatusAttribute() {
        if ( ! is_null( $this->approved_at ) ) {
            return EAlbumStatus::approved;
        } else if ( is_null( $this->approved_at ) && $this->state == EState::disabled ) {
            return EAlbumStatus::rejected;
        }

        return EAlbumStatus::pending;
    }

    public function hotels() {
        return $this->morphedByMany( Hotel::class, 'mediable' );
    }

    public function attractions() {
        return $this->morphedByMany( Attraction::class, 'mediable' );
    }

    public function restaurants() {
        return $this->morphedByMany( Restaurant::class, 'mediable' );
    }

    public function locations() {
        return $this->morphedByMany( Location::class, 'mediable' );
    }

    public function scopeApproved( $q ) {
        return $q->whereNotNull( 'approved_at' );
    }

    public function getBasicInfo() {
        return [
            'id'          => $this->id,
            'url'         => $this->url,
            'description' => $this->description,
            'is_approved' => $this->is_approved,
        ];
    }

    public function getThumbnailUrlAttribute() {
        return imageSize( $this->url, 200 );
    }
}
