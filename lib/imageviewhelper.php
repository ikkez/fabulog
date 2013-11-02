<?php
/**
    imageviewhelper.php
    
    The contents of this file are subject to the terms of the GNU General
    Public License Version 3.0. You may not use this file except in
    compliance with the license. Any of the license terms and conditions
    can be waived if you get permission from the copyright holder.
    
    Copyright (c) 2012 ~ vircuit 
    Christian Knuth <mail@ikkez.de>
 
        @version 0.1.0
        @date: 22.08.13 
 **/

class ImageViewHelper {

    static public function resize($path,$width,$height,$crop=false,$quality=75) {
        $f3 = \Base::instance();
        $new_file_name = $f3->hash($path.$width.$height.$crop.$quality).'.jpg';
        $dst_path = $f3->get('UPLOADS').'cache/';
        list($path, $file) = explode('/', $path);

        if(!is_dir($dst_path))
            mkdir($dst_path);
        elseif(!file_exists($dst_path.$new_file_name)) {
            $imgObj = new \Image($file,false, $path.'/');
            $imgObj->resize($width, $height, $crop);
            $file_data = $imgObj->dump('jpeg',null, $quality);
            $f3->write($dst_path.$new_file_name, $file_data);
        }
        return $dst_path.$new_file_name;
    }

    static public function render($args) {
        $options = array(
            'src','width', 'height', 'crop', 'quality'
        );
        $attr = $args['@attrib'];
        $tmp = \Template::instance();
        foreach ($attr as $key=>&$att) {
            if (preg_match('/{{(.+?)}}/s', $att)) {
                if(in_array($key,$options))
                    $att = $tmp->token($att);
                else
                    $att = $tmp->build($att);
            }
            unset($att);
        }
        $tag_attr = '';
        foreach ($attr as $key => $val)
            if (!in_array($key, $options))
                $tag_attr .= ' '.$key.'="'.$tmp->build($val).'"';
        $crop = isset($attr['crop']) && $attr['crop'] == "true" ? 'true':'false';

        return '<img src="<?php echo \ImageViewHelper::resize('.$attr['src'].','.$attr['width'].','.$attr['height'].','.$crop.');?>"'.$tag_attr.' />';
    }
}