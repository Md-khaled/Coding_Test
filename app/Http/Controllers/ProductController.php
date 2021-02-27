<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use App\Traits\ProductTrait;
use Carbon;
class ProductController extends Controller
{
    use ProductTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
       
        $variants = Variant::all();
        $products=Product::with('prices.variant_one','prices.variant_two','prices.variant_three')->paginate(2);
        // foreach ($products as $key => $value) {
        //   // print_r($value->prices);
        //     foreach ($value->prices as $k => $val) {
        //         //print_r($val->variant_one->variant);
        //     }
        // }
        //return;
        //return $products;
        return view('products.index',compact('products','variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        
        if($this->uploadImage($request)){
            return 'This image field is required';
        }
        $imagepath=$this->uploadImage($request);
        
        return $request;
        return $request->file->getClientOriginalExtension();
       $prices=$request->product_variant_prices;

        $vatrints=[
            'product_variant_one',
            'product_variant_two',
            'product_variant_three',
        ];

        $product_id=Product::create([
            'title'=>$request['title'],
            'sku'=>$request['sku'],
            'description'=>$request['description']
        ])->id;
        
        $cross_products;
        foreach ($request->product_variant as $key => $value) {
            foreach ($value['tags'] as $k => $val) {
                $product_variant_ids[$key][]= ProductVariant::create([
                    'variant'=>$val,
                    'variant_id'=>$value['option'],
                    'product_id'=>$product_id,
                ])->id;  
            }
        }

        $cross_products=collect($product_variant_ids[0]);
        if (array_key_exists(1,$product_variant_ids) && array_key_exists(2,$product_variant_ids)) {
            $cross_products=$cross_products->crossJoin($product_variant_ids[1],$product_variant_ids[2]);
        }elseif (array_key_exists(1,$product_variant_ids)) {
            $cross_products=$cross_products->crossJoin($product_variant_ids[1]);
        }
        if (count($product_variant_ids) == count($product_variant_ids, COUNT_RECURSIVE)) 
        {
          echo 'MyArray is not multidimensional';
        }
        else
        {
            $product_prices=collect([]);
            foreach ($cross_products as $key => $cross_product) {
                $vrnt=[];
                foreach ($cross_product as $tk => $tv) {
                    $vrnt[$vatrints[$tk]]=$tv;
                }
                $product_prices[$key]=array_merge($vrnt,[
                        'price'=>$prices[$key]['price'],
                        'stock'=>$prices[$key]['stock'],
                        'product_id'=>$product_id
                    ]);
            }
            ProductVariantPrice::insert($product_prices->toArray());
            return $product_prices;
        }
            
        return $cross;
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function search(Request $product)
    {
        $title=$product->title;
        $vid=$product->variantid;
        $from=$product->price_from;
        $to=$product->price_to;
        $date=$product->date;

        $products=Product::where([['title', 'LIKE', "%{$title}%"]])
        ->whereDate('created_at',$date)
        ->whereHas('variants', function ($query) use($vid) {
            $query->where('variant_id', '=', $vid);
        })
        ->with(['prices'=>function($query) use($from, $to)
        {
            return $query->with('variant_one','variant_two','variant_three')->whereBetween('price',[$from,$to]);
        }])
        ->paginate(2);

        $variants = Variant::all();
        return view('products.index',compact('products','variants'));
    }
    public function show($product)
    {
       
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //  $id=$product->id;
        // $data['variants'] = Variant::whereHas('product_variants',function ($query) use($id)
        // {
        //     $query->where('product_id',$id);
        // })->get();
        $data['variants'] = Variant::all();
        $data['product']=$product;
        $data['product']['product_variants']=ProductVariant::where('product_id',$product->id)->get()->groupBy('variant_id');
         $data['prices']=$product->load('prices.variant_one','prices.variant_two','prices.variant_three');

        return view('products.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        if ($request->hasFile('file')) {
           return 'sfsdf' ;
        }
        $image=$request->file('file');
        $imageName=$image->getClientOriginalName();
        return $imageName;
        $product->update([
            'title' =>$request->title,
            'sku' =>$request->sku,
            'description' =>$request->description,
        ]);
        $ids=array_column($request->product_variant, 'option');
        $product->productvariants()->detach($ids);
        $product->prices()->delete();
        // foreach ($request->product_variant as $key => $value) {
        //     foreach ($value['tags']  as $k => $val) {
        //         $product->productvariants()->attach($value['option'],['variant'=>$val]);
        //        // $variant->push([$value['option']=>['variant'=>$val]]);
        //     }
        // }
        //         $product->productvariants()->attach($value['option'],['variant'=>$val]);

        // $product->productvariants()->attach($variant->toArray());
         foreach ($request->product_variant as $key => $value) {
            foreach ($value['tags'] as $k => $val) {
                $product_variant_ids[$key][]= ProductVariant::create([
                    'variant'=>$val,
                    'variant_id'=>$value['option'],
                    'product_id'=>$product->id,
                ])->id;  
            }
        }
        return $variant;
        return $request->product_variant;
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
