<div class="form-body">
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif    
    
<div class="form-group form-md-line-input">
    <label class="col-md-2 control-label" for="form_control_1">Product Name</label>
    <div class="col-md-10">
        {!! Form::text('name',(isset($product_list))?$product_list->name:'',['class'=>'form-control','required'=>'required','placeholder'=>'Enter the Product Name']) !!}
        <div class="form-control-focus"></div>
    </div>
</div>

<div class="form-group form-md-line-input">
    <label class="col-md-2 control-label" for="form_control_1">Select Category </label>
    <div class="col-md-10">
        <select name="id_category" required class="form-control" id="">
                @foreach ($category_list as $item)
                    @if (isset($product_list->id_category) && $item->id == $product_list->id_category)
                    <option selected value="{{$item->id}}">{{$item->name}}</option>                        
                    @else
                    <option value="{{$item->id}}">{{$item->name}}</option>                        
                    @endif
                @endforeach
        </select>
        <div class="form-control-focus"></div>            
    </div>
</div>

<div class="form-group form-md-line-input">
    <label class="col-md-2 control-label" for="form_control_1">Template Form</label>
    <div class="col-md-10">
        <select name="template_form" id="">
            @foreach ($template_list as $template)
                @if (isset($product_list->template_form) && $template == $product_list->template_form)
                <option selected value="{{$template}}">{{$template}}</option>                        
                @elseif ($template == 'General' && !isset($product_list->template_form) )
                <option selected value="{{$template}}">{{$template}}</option>                        
                @else 
                <option  value="{{$template}}">{{$template}}</option>                        
                @endif
            @endforeach
        </select>
        <div class="form-control-focus"></div>
    </div>
</div>
    
<div class="form-group form-md-line-input">
    <label class="col-md-2 control-label" for="form_control_1">Description</label>
    <div class="col-md-10">
        {!! Form::textarea('description',(isset($product_list))?$product_list->description:'',['class'=>'form-control','placeholder'=>'Description']) !!}
        <div class="form-control-focus"></div>
    </div>
</div>

<div class="form-group form-md-line-input">
    <label class="col-md-2 control-label" for="form_control_1">Image</label>
    <div class="col-md-10">
        {!! Form::file('image', array('class' => 'image')) !!}
        <div class="form-control-focus"></div>
    </div>
</div>    

<div class="form-group form-md-line-input  control-group increment">
    <label class="col-md-2 control-label" for="form_control_1">Gallery</label>
    <div class="col-md-10"> --}}
        {!! Form::file('image', array('class' => 'image')) !!}
        <input type="file" name="gallery[]" style="display:  inline-block;" >
        {!! Form::file('gallery[]', array('style' => 'display: inline-block;')) !!}            
        
         <button class="btn btn-success" type="button"><i class=""></i>Add</button>            
        <div class="form-control-focus"></div>
    </div>
</div>  


@if (isset($gallery) && is_object($gallery))
    @foreach ($gallery as $item)
        <div class="form-group form-md-line-input control-group">
            <label class="col-md-2 control-label" for="form_control_1">Gallery</label>
            <div class="col-md-10">{{$item->image}}
                {!! Form::hidden('id_imgs_old[]',$item->id) !!}                    
                {{-- <input type="file" name="gallery[]" style="display: inline-block;">   --}}
                {!! Form::file('gallery[]', array('style' => 'display: inline-block;' ,'value' =>  asset('products/'.$item->image))) !!}            
                {{-- {!! Form::file('image', array('class' => 'image')) !!} --}}
                <button class="btn btn-danger" type="button"><i class=""></i> Remove</button>                                        
                <div class="form-control-focus"></div>
            </div>
        </div>  
    @endforeach
@endif
<div class="clone hide"> 
    <div class="form-group form-md-line-input control-group">
    <label class="col-md-2 control-label" for="form_control_1">Gallery</label>
    <div class="col-md-10">
        {!! Form::file('gallery[]', array('style' => 'display: inline-block;')) !!}            
        <button class="btn btn-danger" type="button"><i class=""></i> Remove</button>                                        
        <div class="form-control-focus"></div>
    </div>
    </div>  
</div>
<div style="clear:both"></div>
</div>
<div class="form-actions">
<div class="row">
    <div class="col-md-offset-2 col-md-10">
        <a href="{{ route('broker::product-list') }}" class="btn default">Cancel</a>
        {!! Form::submit('Save',['class'=>'btn blue']) !!}
    </div>
</div>
</div>


