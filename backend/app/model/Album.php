<?php

namespace app\model;

use think\Model;
use think\model\Collection;

class Album extends Model
{
    protected $table = 'albums';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id'          => 'integer',
        'category_id' => 'integer',
        'min_level'   => 'integer',
        'view_count'  => 'integer',
        'status'      => 'integer',
        'sort_order'  => 'integer',
        'creator_id'  => 'integer',
    ];

    protected $append = [];

    public function category()
    {
        return $this->belongsTo(AlbumCategory::class, 'category_id', 'id');
    }

    public function pages()
    {
        return $this->hasMany(AlbumPage::class, 'album_id', 'id')->order('page_number', 'asc');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function getCoverImageUrlAttr()
    {
        return $this->cover_image ? get_upload_url($this->cover_image) : '';
    }

    public function getBackgroundImageUrlAttr()
    {
        return $this->background_image ? get_upload_url($this->background_image) : '';
    }

    public function getQrcodeImageUrlAttr()
    {
        return $this->qrcode_image ? get_upload_url($this->qrcode_image) : '';
    }

    public function getQrcodeLogoUrlAttr()
    {
        return $this->qrcode_logo ? get_upload_url($this->qrcode_logo) : '';
    }

    public function getHasPasswordAttr()
    {
        return !empty($this->share_password);
    }

    public function toAdminList()
    {
        $data = $this->toArray();
        $data['cover_image_url'] = $this->cover_image_url;
        $data['background_image_url'] = $this->background_image_url;
        $data['qrcode_image_url'] = $this->qrcode_image_url;
        return $data;
    }

    public function toPublicList()
    {
        $data = $this->toArray();
        $data['cover_image_url'] = $this->cover_image_url;
        $data['background_image_url'] = $this->background_image_url;
        $data['has_password'] = $this->has_password;
        unset($data['share_password']);
        return $data;
    }

    public function toAdminDetail()
    {
        $data = $this->toArray();
        $data['cover_image_url'] = $this->cover_image_url;
        $data['background_image_url'] = $this->background_image_url;
        $data['qrcode_image_url'] = $this->qrcode_image_url;
        $data['qrcode_logo_url'] = $this->qrcode_logo_url;
        return $data;
    }

    public static function loadPageCount(Collection $albums)
    {
        if ($albums->isEmpty()) {
            return;
        }
        $albumIds = $albums->column('id');
        $pageCounts = AlbumPage::whereIn('album_id', $albumIds)
            ->group('album_id')
            ->column('COUNT(*)', 'album_id');
        foreach ($albums as $album) {
            $album->page_count = (int)($pageCounts[$album->id] ?? 0);
        }
    }
}
