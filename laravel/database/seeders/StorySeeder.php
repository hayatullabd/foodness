<?php

namespace Database\Seeders;

use App\Models\Story;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    public function run(): void
    {
        Story::query()->truncate();

        $items = [
            [
                'title' => 'New In',
                'tag' => 'JUST LANDED',
                'media_type' => 'image',
                'media_path' => 'products/men-pnj-01.jpg',
                'thumbnail_path' => 'products/men-pnj-01.jpg',
                'link_url' => '/shop',
                'link_text' => 'SHOP NOW',
                'duration' => 6,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Everyday',
                'tag' => 'SOFT SUPPORT',
                'media_type' => 'video',
                'media_path' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'thumbnail_path' => 'products/men-sht-03.jpg',
                'link_url' => '/shop',
                'link_text' => 'SHOP NOW',
                'duration' => 15,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Push-Up',
                'tag' => 'YOUR SHAPE, YOUR WAY',
                'media_type' => 'image',
                'media_path' => 'products/wmn-sar-01.jpg',
                'thumbnail_path' => 'products/wmn-sar-01.jpg',
                'link_url' => '/shop',
                'link_text' => 'SHOP NOW',
                'duration' => 6,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Nightwear',
                'tag' => 'AFTER HOURS',
                'media_type' => 'image',
                'media_path' => 'products/wmn-aby-05.jpg',
                'thumbnail_path' => 'products/wmn-aby-05.jpg',
                'link_url' => '/shop',
                'link_text' => 'SHOP NOW',
                'duration' => 6,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Shapewear',
                'tag' => 'SMOOTH & SCULPT',
                'media_type' => 'image',
                'media_path' => 'products/wmn-krt-03.jpg',
                'thumbnail_path' => 'products/wmn-krt-03.jpg',
                'link_url' => '/shop',
                'link_text' => 'SHOP NOW',
                'duration' => 6,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Offers',
                'tag' => 'LIMITED TIME',
                'media_type' => 'image',
                'media_path' => 'products/men-pol-05.jpg',
                'thumbnail_path' => 'products/men-pol-05.jpg',
                'link_url' => '/shop',
                'link_text' => 'SHOP NOW',
                'duration' => 6,
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            Story::query()->create($item);
        }
    }
}
