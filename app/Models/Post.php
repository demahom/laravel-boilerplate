<?php

namespace App\Models;

use Carbon\Carbon;
use Plank\Mediable\Media;
use Plank\Mediable\Mediable;
use Laravel\Scout\Searchable;
use App\Models\Traits\Metable;
use App\Models\Traits\Taggable;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\Post.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $status
 * @property bool $promoted
 * @property bool $pinned
 * @property \Carbon\Carbon|null $published_at
 * @property string|null $unpublished_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property mixed $featured_image_path
 * @property mixed $meta_description
 * @property mixed $meta_title
 * @property mixed $published
 * @property mixed $state
 * @property mixed $status_label
 * @property \Illuminate\Database\Eloquent\Collection|\Plank\Mediable\Media[] $media
 * @property \App\Models\Meta $meta
 * @property \App\Models\User|null $owner
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\PostTranslation[] $translations
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post listsTranslations($translationField)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post notTranslatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post orWhereTranslation($key, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post orWhereTranslationLike($key, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post published()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post translated()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post translatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereHasMedia($tags, $match_all = false)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereHasMediaMatchAll($tags)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post wherePinned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post wherePromoted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereTranslation($key, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereTranslationLike($key, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereUnpublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post withMedia($tags = array(), $match_all = false)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post withMediaMatchAll($tags = array())
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post withOwner(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post withTag(\App\Models\Tag $tag)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Post withTranslation()
 * @mixin \Eloquent
 */
class Post extends Model
{
    use Searchable;
    use Mediable;
    use Taggable;
    use Metable;
    use Translatable;

    public $asYouType = true;

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = [
        'title',
        'summary',
        'body',
        'slug',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'published_at',
        'unpublished_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'state',
        'status_label',
        'featured_image_path',
    ];

    /**
     * The attributes that are eager loaded.
     *
     * @var array
     */
    protected $casts = [
        'pinned' => 'boolean',
        'promoted' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'published_at',
        'unpublished_at',
        'pinned',
        'promoted',
    ];

    protected $with = [
        'translations',
        'media',
        'owner',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleted(function (Post $post) {
            $post->meta->delete();
        });
    }

    const DRAFT = 0;
    const PENDING = 1;
    const PUBLISHED = 2;

    public static function getStatuses()
    {
        return [
            self::DRAFT => 'labels.backend.posts.statuses.draft',
            self::PENDING => 'labels.backend.posts.statuses.pending',
            self::PUBLISHED => 'labels.backend.posts.statuses.published',
        ];
    }

    public static function getStates()
    {
        return [
            self::DRAFT => 'danger',
            self::PENDING => 'warning',
            self::PUBLISHED => 'success',
        ];
    }

    public function getStatusLabelAttribute()
    {
        return self::getStatuses()[$this->status];
    }

    public function getStateAttribute()
    {
        return self::getStates()[$this->status];
    }

    public function getPublishedAttribute()
    {
        return self::PUBLISHED === $this->status;
    }

    public function getFeaturedImagePathAttribute()
    {
        /** @var Media $media */
        if ($media = $this->getMedia('featured image')->first()) {
            return $media->getDiskPath();
        }

        return 'placeholder.png';
    }

    public function getMetaTitleAttribute()
    {
        return null !== $this->meta && ! empty($this->meta->title) ? $this->meta->title : $this->title;
    }

    public function getMetaDescriptionAttribute()
    {
        return null !== $this->meta && ! empty($this->meta->description) ? $this->meta->description : $this->summary;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function setPublishedAtAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['published_at'] = Carbon::createFromFormat('Y-m-d H:i', $value);
        } else {
            $this->attributes['published_at'] = $value;
        }
    }

    public function setUnPublishedAtAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['unpublished_at'] = Carbon::createFromFormat('Y-m-d H:i', $value);
        } else {
            $this->attributes['unpublished_at'] = $value;
        }
    }

    /**
     * Scope a query to only include published articles.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished(Builder $query)
    {
        return $query->where('status', '=', self::PUBLISHED);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User                      $user
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithOwner(Builder $query, User $user)
    {
        return $query->where('user_id', '=', $user->id);
    }

    /**
     * Delete media physically.
     *
     * @throws \Exception
     */
    public function handleMediableDeletion()
    {
        $this->getMedia('featured image')->each(function (Media $media) {
            $media->delete();
        });
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->description,
            'body' => $this->body,
        ];
    }
}
