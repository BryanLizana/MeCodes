<?php

namespace App\Modules\Broker\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use Redirect;
use Config;
use Caffeinated\Flash\Facades\Flash;
use Illuminate\Support\Facades\Storage;




class ProductsController extends Controller
{
    //
    public function store(Request $request)
    {
        try {
            // $this->validate($request, [
            //     'filename' => 'required',
            //     'filename.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            // ]);
            
            $path = ($request->hasFile('image'))?  Storage::disk('products')->put('images', $request->file('image')) : 'images/default.png';
            
            $form_values = $request->all();
            $form_values['image'] = $path;
            $form_values['status'] = 1;
            $form_values['slug'] = self::slugify($form_values['name']);
                    
            $Inventory = ProductsInventory::create(array('quantity' =>$form_values['quantity'],'quantity_min_alert' =>$form_values['quantity_min_alert'] ,'email_min_alert' =>$form_values['email_min_alert'] ));
            $form_values['id_inventory'] = $Inventory->id;
            // $form_values['product_external_url'] = "";
            $item = Products::create($form_values);  
            $id_imgs_old = (!empty($form_values['id_imgs_old']))  ? $form_values['id_imgs_old'] : [0];
            $gallery = ($request->hasFile('gallery')) ? $request->file('gallery') : array();

            self::addGallery($gallery, $item->id, $id_imgs_old);
            Flash::success('Created successfully');

        } catch (\Illuminate\Database\QueryException $ex) {
            Flash::error('error:'.$ex->getMessage());
        }
        return Redirect::route('broker::product-list');
    }


    public function update(Request $request, $id)
    {
        $product = Products::find($id);
        $form_values = $request->all();
        try {
            $path = ($request->hasFile('image'))? Storage::disk('products')->put('images', $request->file('image')) : $product->image;
            
            if ($path != $product->image) {
                Storage::disk('products')->delete($product->image);
            }

            $product->name = $form_values['name'];
            $product->subtitle = $form_values['subtitle'];
            $product->slug = self::slugify($form_values['name']); 
            $product->description = $form_values['description'];
            $product->price = $form_values['price'];
            // $product->price_fake = $form_values['price_fake']; //TODO: Por ahora deshabilitado
            $product->id_category = $form_values['id_category'];
            $product->template_form = $form_values['template_form'];

            $product->pr_no_nsd_services = $form_values['pr_no_nsd_services'];
            $product->product_external_url = $form_values['product_external_url'];

            // Update quantity
            $Inventory = ProductsInventory::find($product->id_inventory);        
            $Inventory->quantity = $form_values['quantity'];            
            $Inventory->email_min_alert = $form_values['email_min_alert'];            
            $Inventory->quantity_min_alert = $form_values['quantity_min_alert'];            
            $Inventory->save();

            $product->image = $path;
            $product->status = 1;
            $product_final =  $product->save();
            
            $id_imgs_old = (!empty($form_values['id_imgs_old']))  ? $form_values['id_imgs_old'] : [0];
            $gallery = ($request->hasFile('gallery')) ? $request->file('gallery') : array();
            self::addGallery($gallery, $id, $id_imgs_old);
            Flash::success('Update successfully');

        } catch (\Illuminate\Database\QueryException $ex) {
            Flash::error('error:'.$ex->getMessage());

        }
        return Redirect::route('broker::product-list');
    }



    public function destroy($id)
    {
        try {

            $image_to_delete = ProductsGalleries::where('id_product', $id)->get();
            if (!$image_to_delete->isEmpty()) {
                foreach ($image_to_delete as $row) {
                    $row->delete();
                }
            }

            Flash::success('Delete successfully');

        } catch (\Illuminate\Database\QueryException $ex) {
            Flash::error('error:'.$ex->getMessage());
        }

        return Redirect::route('broker::product-list');
    }

    public function addGallery($galleries, $id_product, $id_imgs_old = null)
    {
        try {
            $image_to_delete = ProductsGalleries::where('id_product', $id_product)->whereNotIn('id', $id_imgs_old)->get();
            $delete_after[] = array();
            if (!$image_to_delete->isEmpty()) {
                foreach ($image_to_delete as $row) {
                    $delete_after[] = $row->image;
                    $row->delete();
                }
            }

            foreach ($galleries as $item) {
                $path = (true) ? Storage::disk('products')->put('images', $item) : 'default.png';
                $gallery['name'] = 'default';
                $gallery['image'] = $path;
                $gallery['id_product'] = $id_product;
                
                $r = ProductsGalleries::create($gallery);
            }

            foreach ($delete_after as $del) {
                Storage::disk('products')->delete($del);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            Flash::error('Error QueryException in add Gallery:'.$ex->getMessage());
        }
    }


    public function getListTemplateForm(Type $var = null)
    {
        $d = dir(app_path('Modules/Publico/Resources/Views/Forms'));
        while (false !== ($entry = $d->read())) {
            if ($entry != '..' &&  $entry != '.') {
                $list_path_form[] = $entry;
            }
        }
        $d->close();

        return $list_path_form;
    }
}
