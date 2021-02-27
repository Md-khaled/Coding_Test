<?php
namespace App\Traits;

use Illuminate\Http\Request;
use Image;
trait ProductTrait{

	public function uploadImage(Request $file,$id=null)
    {
        if (!$file->hasFile('file') && $id==null) {
           return false ;
        }
        if (!$file->hasFile('file') && $id!=null) {
        	return true;
        }
        $image=$file->file('file');
        $imageName=time().'.'.$image->getClientOriginalName();
        Image::make($image)->resize(215, 215)->save(public_path('images/'.$imageName));
        return $imageName;
    }
}