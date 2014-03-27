<?php

/**
 * @author Mr. Box
 * @copyright 2014
 * 
 * 百度富文本编辑器编辑出的内容转成数组。根据<p>元素
 * 分文本、图片两种
 */

/**
 * ueditorHtml2Array()
 * 
 * @param String $content 百度编辑器输出的源代码
 * @param integer $imgMaxWidth  限制显示图片的最大宽度。如果为0则输出原始大小。不为0等比缩放
 * @param string $imgBasePath  图片路径：源代码中类似 '/uploads/images/xxxx.jpg'，输出：$imgBasePath.'/uploads/images/xxxx.jpg'
 * @return Array
 */
function ueditorHtml2Array($content, $imgMaxWidth = 0, $imgBasePath = ''){
    $content = preg_replace("/<p>\s*<br\s*\/>\s*<\/p>/U", '<p>--break--</p>', $content); //如果要忽略手动换行，把此行代码注释掉
    $content = preg_replace("/<\/p>\s*<p[^>]*>/", '$$$###$$$', $content);
    $content = preg_replace("/<p[^>]*>|<\/p>/", '', $content);
    $matchs = explode('$$$###$$$', $content);
    
    $data = array();
    foreach($matchs as $key => $va){
        $va = preg_replace("/&nbsp;|　/i", '', strip_tags($va,'<img>,<br>'));
        $va = trim($va);
        $va = preg_replace("/<br[^>]*>/", '<br>',$va);
        if(strlen($va)){
            $imgArr = array();
            $imgPath = '';
            $imgName = '';
            $word = '';
            
            $hasimg = strpos($va,'<img');
            if($hasimg !== false){
                preg_match("/<img.+src\s*=\s*\"([^\"]+)\"[^>]*>/i",$va,$imgArr);
                if(isset($imgArr[1])){
                    $imgPath = $imgBasePath.$imgArr[1];
                    
                    $imgLastA = strrpos($imgArr[1],"/");
                    $imgLastD = strrpos($imgArr[1],".");
                    $l = $imgLastD - strlen($imgArr[1]);
                    $imgName = substr($imgArr[1],$imgLastA+1, $l);
                    
                    //下面两种方式，第二种根据本地路径，效率较快，第一种如果$imgBasePath为http远程路径，效率较慢
                    #$imgsize = @getimagesize($imgPath);
                    $imgsize = @getimagesize(getcwd().$imgArr[1]);
                    if($imgsize === false){
                        $imgHeight = 0;
                        $imgWidth = 0;
                    }else{
                        $picW = $imgsize[0];
                        $picH = $imgsize[1];
                        if($imgMaxWidth == 0){
                            //按实际高度输出
                            $imgHeight = $picH;
                            $imgWidth = $picW;
                        }else{
                            //根据宽度，等比输出高度
                            if($imgMaxWidth < $picW){
                                $imgHeight = round($imgsize[1]*$imgMaxWidth/$imgsize[0]);
                                $imgWidth = $imgMaxWidth;
                            }else{
                                $imgWidth = $picW;
                                $imgHeight = $picH;
                            }
                            
                        }
                    }
                    $tmpArr = explode($imgArr[0], $va);
                    if(count($tmpArr) > 1){
                        $tmpArr[0] = preg_replace("/[^\S]+/", '', $tmpArr[0]);
                        $tmpArr[1] = preg_replace("/[^\S]+/", '', $tmpArr[1]);
                        if($tmpArr[0] != '' && $tmpArr[1] != ''){//文字，图片，文字
                            
                            $tmpArr2 = explode("<br>",$tmpArr[0]);
                            foreach($tmpArr2 as $vas){
                                $vas = trim($vas);
                                if($vas){
                                    $data[] = array('word'=>$vas);
                                }
                            }
                            
                            $data[] = array('img'=>$imgPath,'img_name'=>$imgName,'height'=>$imgHeight,'width'=>$imgWidth);
                            
                            $tmpArr2 = explode("<br>",$tmpArr[1]);
                            foreach($tmpArr2 as $vas){
                                $vas = trim($vas);
                                if($vas){
                                    $data[] = array('word'=>$vas);
                                }
                            }
                        }elseif($tmpArr[0] != ''){ //文字，图片
                            $tmpArr2 = explode("<br>",$tmpArr[0]);
                            foreach($tmpArr2 as $vas){
                                $vas = trim($vas);
                                if($vas){
                                    $data[] = array('word'=>$vas);
                                }
                            }
                            $data[] = array('img'=>$imgPath,'img_name'=>$imgName,'height'=>$imgHeight,'width'=>$imgWidth);
                        }elseif($tmpArr[1] != ''){//图片，文字
                            $data[] = array('img'=>$imgPath,'img_name'=>$imgName,'height'=>$imgHeight,'width'=>$imgWidth);
                            $tmpArr2 = explode("<br>",$tmpArr[1]);
                            foreach($tmpArr2 as $vas){
                                $vas = trim($vas);
                                if($vas){
                                    $data[] = array('word'=>$vas);
                                }
                            }
                        }else{ //只有图片
                            $data[] = array('img'=>$imgPath,'img_name'=>$imgName,'height'=>$imgHeight,'width'=>$imgWidth);
                        }
                    }else{
                        //正常不会执行到此
                    }
                }else{
                    //正常不会执行到此
                }
            }else{ //只有文字
                $tmpArr2 = explode("<br>",$va);
                foreach($tmpArr2 as $vas){
                    $vas = trim($vas);
                    if($vas){
                        if($vas == '--break--') $vas = ' ';
                        $data[] = array('word'=>$vas);
                    }
                }
            }
            
        }
    }
    return $data;
}

?>