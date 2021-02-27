<?php
namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Image;
trait ProductTrait{
    protected $vatrints=[
                'product_variant_one',
                'product_variant_two',
                'product_variant_three',
            ];
	public function uploadImage(Request $files,$id=null)
    {
    	$imageName=$files->product_image;
        $images=[];
    	foreach ($imageName as $key => $value) {
    		$images[]=$this->imageprocess($value);
    	}
        return $images;
    }

    public function imageprocess($image)
	{
	    $exploed1 = explode(";", $image);
	    $exploed2 = explode("/", $exploed1[0]);
	    $filename =  uniqid().'.'.$exploed2[1];
	    Image::make($image)->resize(215, 215)->save(public_path('images/'.$filename));
	    return $filename;
	}

    public function insertProductVariant($request,$product_id,$id=null)
    {
        foreach ($request->product_variant as $key => $value) {
            foreach ($value['tags'] as $k => $val) {
                $product_variant_ids[$key][]= ProductVariant::create([
                    'variant'=>$val,
                    'variant_id'=>$value['option'],
                    'product_id'=>$product_id,
                ])->id;  
            }
        }
        return $product_variant_ids;
    }
    public function insertProductVariantPrices($request, $product_variant_ids,$product_id)
    {
        $prices=$request->product_variant_prices;
        $cross_products=collect($product_variant_ids[0]);

        if (array_key_exists(1,$product_variant_ids) && array_key_exists(2,$product_variant_ids)) {
            $cross_products=$cross_products->crossJoin($product_variant_ids[1],$product_variant_ids[2]);
        }elseif (array_key_exists(1,$product_variant_ids)) {
            $cross_products=$cross_products->crossJoin($product_variant_ids[1]);
        }
        if (count($product_variant_ids) < 2) 
        {
            foreach ($cross_products as $index => $value) {
                ProductVariantPrice::insert([
                    $this->vatrints[$index]=>$value,
                    'price'=>$prices[$index]['price'],
                    'stock'=>$prices[$index]['stock'],
                    'product_id'=>$product_id
                 ]);
            }
        }
        else
        {
            //return count($product_variant_ids);
            $product_prices=collect([]);
            foreach ($cross_products as $key => $cross_product) {
                $vrnt=[];
                foreach ($cross_product as $tk => $tv) {
                    $vrnt[$this->vatrints[$tk]]=$tv;
                }
                $product_prices[$key]=array_merge($vrnt,[
                        'price'=>$prices[$key]['price'],
                        'stock'=>$prices[$key]['stock'],
                        'product_id'=>$product_id
                    ]);
            }
            ProductVariantPrice::insert($product_prices->toArray());
            return response()->json(['success'=>true,200]);
        }
    }

    private function data_validate($request,$id=null)
    {
        $this->validate($request, [
            'title' => 'bail|required|string|max:100',
            'sku' => 'bail|required|max:100|unique:products,sku,'.($id!=null?$id:''),
            'description' => 'required',
            'product_variant.*.tags' => 'required',
            'product_image' => 'required|array|min:1',
            'product_image.*' => 'required',
            //'currency_code' => 'bail|required|string|unique:currencies,currency_code,'.($id!=null?$id:'')
        ],
        [
            'product_variant.*.tags.required'=>'The variant tag field is required',
        ]);
    }
}