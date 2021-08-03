<?php

namespace Kaban\Models;

use Illuminate\Database\Eloquent\Model;
use jDate;
use Kaban\Core\Enums\EState;
use Kaban\Core\Models\BaseModel;
use Kaban\General\Enums\EPostStatus;
use Kaban\General\Enums\ETagType;
use Kaban\General\Interfaces\ICommentable;
use Kaban\General\Interfaces\IHittable;
use Kaban\General\Interfaces\IReactable;
use Kaban\General\Interfaces\IValuable;
use Kaban\General\Services\Content;

class Post extends Model {
    use HasMedia;

    protected $softDelete = true;
//    BaseModel implements ICommentable, IHittable {
//    use Hittable, Commentable;
//    protected $fillable = [ 'title', 'excerpt', 'contact' ];
    protected $guarded = [];

    //Relations

    public function __construct( array $attributes = [] ) {
        parent::__construct( $attributes );
    }

    public static function table() {
        return ( new self )->getTable();
    }


    public function author() {
        return $this->belongsTo( User::class, 'author_id' );
    }

    public function scopeValid( $q ) {
        $q->where( 'status', EPostStatus::approved );
    }

    public function category() {
        return $this->belongsTo( Category::class );
    }


    public function tags() {
        return $this->morphToMany( Tag::class, 'taggable' );
    }

    public function syncTags( $arr ) {
        foreach ( $arr as $tag ) {
            $tags[] = isset( $tag->text ) ? $tag->text : $tag;
        }
        $existed_tags    = Tag::whereIn( 'name', $tags )->where( 'type', ETagType::post )->get();
        $tagIdsToAttach  = $existed_tags->pluck( 'id' )->toArray();
        $existedTagNames = $existed_tags->pluck( 'name' )->toArray();

        foreach ( $tags as $tag ) {
            if ( ! in_array( $tag, $existedTagNames ) ) {
                $tagModel         = Tag::where( 'name', '!=', $tag )->create( [
                    'name' => $tag,
                    'type' => ETagType::post
                ] );
                $tagIdsToAttach[] = $tagModel->id;
            }

        }

        $this->tags()->sync( $tagIdsToAttach );

        return '-' . implode( '-', $this->tags()->pluck( 'id' )->values()->toArray() ) . '-';
    }
    //    public function image() {
    //        return $this->belongsTo( Media::class, 'image_id', 'id', 'image' );
    //    }
    public function setSlugAttribute( $value ) {
        $this->attributes['slug'] = slugify( $value );
    }

    public function setSlugFaAttribute( $value ) {
        $this->attributes['slug_fa'] = slugify( $value );
    }
}
