<?php


namespace Kaban\Components\Customer\Words\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Kaban\Components\Customer\Words\Resources\GetAllWordsResource;
use Kaban\Components\Customer\Words\Resources\GetWordEditItemResource;
use Kaban\Components\Customer\Words\Resources\GetWordTagsResource;
use Kaban\Components\Customer\Words\Resources\GetSearchedWordsResource;
use Kaban\Core\Controllers\CustomerBaseController;
use Kaban\General\Enums\EMediaType;
use Kaban\General\Enums\EWordStatus;
use Kaban\General\Enums\ETagType;
use Kaban\General\Services\Media\MediaService;
use Kaban\Models\Word;
use Kaban\Models\Tag;

class WordsController extends CustomerBaseController {
    private $mediaService;

    public function __construct( MediaService $mediaService ) {
        $this->mediaService = $mediaService;
    }

    public function index() {
        return view( 'CustomerWords::index' );
    }

    public function edit( $id ) {
        $word = Word::with( 'tags', 'media' )->findOrFail( $id );

        return new GetWordEditItemResource( $word );
    }

    public function search() {
        $word = Word::with( 'media' )->paginate( 10 );

        return GetSearchedWordsResource::collection( $word );
    }

    public function searchTags( Request $request ) {
        $tags = Tag::when( $request->val, function ( $q ) use ( $request ) {
            $q->where( 'name', 'like', "%$request->val%" );
//        } )->where( 'type', ETagType::word )->take( 10 )->get()->map->only( 'id', 'name' );
        } )->where( 'type', ETagType::word )->paginate( 10 );

        return GetWordTagsResource::collection( $tags );
        $word = Word::with( 'media' )->paginate( 10 );

        return GetSearchedWordsResource::collection( $word );
    }

    public function all( Request $request ) {
        $sortType = 'DESC';
        $sortBy   = 'id';

        if ( ! empty( $request->sortBy[0] ) && in_array( $request->sortBy[0], [ 'id', 'title' ] ) ) {
            $sortBy = $request->sortBy[0];
        }
        if ( empty( $request->sortDesc[0] ) ) {
            $sortType = 'ASC';
        }

        $words = Word::when( $request->search, function ( $q ) use ( $request ) {
            $q->where( 'title', 'like', "%$request->search%" );
        } )->orderBy( $sortBy, $sortType )->paginate( $request->itemsPerPage, [ '*' ], 'ascasc', $request->page );

        return GetAllWordsResource::collection( $words );
//        return new GetAllWordsResource( $words );
    }

    public function store( Request $request ) {
        $uid  = auth()->id();
        $form = json_decode( $request->form );

        $word = Word::create( [
            'content'     => $form->content,
            'title'       => $form->title,
            'excerpt'     => $form->excerpt,
            'category_id' => $form->categories,
            'author_id'   => $uid,
            'created_by'  => $uid,
            'updated_by'  => $uid,
            'status'      => EWordStatus::approved,
            'slug'        => slugify( $form->title )
        ] );
        $this->updateMedia( $request, $word );

        $tagIds = $word->syncTags( $form->tags );

        $word->tag_ids = $tagIds;
        $word->save();
    }

    public function update( Request $request ) {
        $uid  = auth()->id();
        $form = json_decode( $request->form );
        $word = Word::find( $form->id );


        $this->updateMedia( $request, $word );

        $word->update( [
            'content'     => $form->content,
            'title'       => $form->title,
            'excerpt'     => $form->excerpt,
            'category_id' => $form->categories,
            'author_id'   => $uid,
            'created_by'  => $uid,
            'updated_by'  => $uid,
            'status'      => $form->status->value,
            'slug'        => slugify( $form->title )
        ] );
        $tagIds = $word->syncTags( $form->tags );

        $word->tag_ids = $tagIds;
        $word->save();

    }

    public function destroy( $id ) {
        $status = Word::findOrFail( $id )->delete();

        return 'done';
    }

    private function updateMedia( Request $request, Word $word ) {
        $oldWorder   = $word->the_poster;
        $oldBackdrop = $word->the_backdrop;
        $this->mediaService->getUploader()->setConfigKey( 'word' );
        //        dd( $word->the_poster );
        if ( $request->file( 'poster' ) ) {
            $mediaFileWorder = MediaService::fromUploadedFile( $request->file( 'poster' ) );
            if ( $this->mediaService->upload( $mediaFileWorder ) && $this->mediaService->uploadThumb( $mediaFileWorder ) ) {
                $data  = [
                    'type'                => EMediaType::poster,
                    'approved_at'         => Carbon::now(),
                    'thumbnail_full_path' => $mediaFileWorder->getFullThumbnailPath(),
                ];
                $media = $this->mediaService->createMedia( $mediaFileWorder, $data );

                if ( $oldWorder ) {
                    $this->mediaService->removeMedia( $word->the_poster );
                }

                $this->mediaService->attachMedia( $media, $word, 'poster', [] );
            }
        }
        if ( $request->file( 'backdrop' ) ) {

            $mediaFileBackdrop = MediaService::fromUploadedFile( $request->file( 'backdrop' ) );
            if ( $this->mediaService->upload( $mediaFileBackdrop ) && $this->mediaService->uploadThumb( $mediaFileBackdrop ) ) {
                $data  = [
                    'type'                => EMediaType::backdrop,
                    'approved_at'         => Carbon::now(),
                    'thumbnail_full_path' => $mediaFileBackdrop->getFullThumbnailPath(),
                ];
                $media = $this->mediaService->createMedia( $mediaFileBackdrop, $data );

                if ( $oldBackdrop ) {
                    $this->mediaService->removeMedia( $word->the_backdrop );
                }
                $this->mediaService->attachMedia( $media, $word, 'wordsBackdrop', [] );
            }
        }
    }
}
