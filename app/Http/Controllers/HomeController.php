<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Dummy product data (mocked for now)
        $products = [
            (object)[
                'id' => 1,
                'name' => 'Fresh Apples',
                'price' => 150,
                'image' => 'https://5.imimg.com/data5/AK/RA/MY-68428614/apple.jpg',
                'rating' => 4.5,
                'num_reviews' => 23,
            ],
            (object)[
                'id' => 2,
                'name' => 'Organic Chicken',
                'price' => 550,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSVF1zDeCt5CYC3jtNXrZFiYCDQOlaA3EeBUQ&s',
                'rating' => 4.8,
                'num_reviews' => 17,
            ],
        ];

        $categories = [
            (object)[
                'name' => 'Fruits & Vegetables',
                'slug' => 'fruits-vegetables',
                'image' => 'https://domf5oio6qrcr.cloudfront.net/medialibrary/11499/3b360279-8b43-40f3-9b11-604749128187.jpg',
            ],
            (object)[
                'name' => 'Meat & Poultry',
                'slug' => 'meat-poultry',
                'image' => 'https://cdn.prod.website-files.com/63cf34956bc59159af577c42/63cf34956bc5912d47578a9b_Meat-%2525252526-Poultry-feature-image.jpeg',
            ],
            (object)[
                'name' => 'Seafood',
                'slug' => 'seafood',
                'image' => 'https://domf5oio6qrcr.cloudfront.net/medialibrary/16013/p1-seafoodcollage-hh0125-gi1185677996.jpg',
            ],
            (object)[
                'name' => 'Bakery Items',
                'slug' => 'bakery',
                'image' => 'https://img.freepik.com/free-photo/sweet-pastry-assortment-top-view_23-2148516578.jpg?semt=ais_hybrid&w=740',
            ],
            (object)[
                'name' => 'Cheese & Deli',
                'slug' => 'cheese-deli',
                'image' => 'https://delawarefarmersmarket.com/wp-content/uploads/2019/05/Stoltzfus-Cheese-Deli-5.jpg',
            ],
        ];
        
        $pages = 1;
        $currentPage = 1;
        
        return view('home', compact('products', 'pages', 'currentPage', 'categories'));
    }
}
