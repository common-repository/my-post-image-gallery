<?php
/*
Plugin Name: Post Image Gallery
Plugin URI: http://hawkenterprises.org/post-image-gallery
Description: Grabs source for all images posts and display's them. This looks for <img src=""> tag thus bypasses post thumbs and wordpress
Version: 2.3.0
Author: GRX3
Author URI: http://grx3.com
*/
if(isset($_REQUEST['build'])){
	post_image_build_cache();
	die();
}

function post_image_gallery(){
	global $post_image_content;
	echo $post_image_content;
}
function post_image_build_cache(){
		global $wpdb;
		$n_posts = 1000;
		$resize = false;
		$width = $height = 50;
		$r = $wpdb->get_results('SELECT ID,post_title,post_content FROM '.$wpdb->prefix.'posts ORDER BY RAND() LIMIT '.$n_posts);
		foreach($r as $v){
			$created = false;
			if(!file_exists(WP_PLUGIN_DIR.'/postimagegallery/cache/'.$v->ID)){
				$created = true;
				preg_match_all('!http://[a-z0-9\-\.\/]+\.(?:jpe?g|png|gif)!Ui',$v->post_content,$res);
				$srcs = $res[0];
				foreach($srcs as $k=>$src){
					if($resize){
						$out = WP_PLUGIN_DIR.'/postimagegallery/cache/'.$v->ID;
						$filename = $src;
						$im_info = @getimagesize($filename);
						if($im_info != false){
							$image_type = $image_info[2];
							
							$im_s = file_get_contents($src);
							$im_s = substr( $im_s, 0, strrpos( $im_s, '</iframe>' ) );
							$im = imagecreatefromstring($im_s);
							//if($im == false) continue;						  
							$new_image = imagecreatetruecolor($width, $height);
							@imagecopyresampled($new_image, $im, 0, 0, 0, 0, $width, $height, $im_info[0], $im_info[1]);
							$im = $new_image;
							if( $image_type == IMAGETYPE_JPEG ) 
								 imagejpeg($im,$out,75);
							elseif( $image_type == IMAGETYPE_GIF ) 
								 imagegif($im,$out);         
							elseif( $image_type == IMAGETYPE_PNG ) 
								 imagepng($im,$out);			
						}else
							continue;
					
					}else{
						if(($cx = file_get_contents($src))==false) continue;
						else
					 		file_put_contents(WP_PLUGIN_DIR.'/postimagegallery/cache/'.$v->ID,$cx);
					continue;
				}
			}
			echo 'Processed '.$v->ID.' - '.$v->post_title.' Created: '.var_export($created,true).'<br>';
			}
		}
}
function post_image_gallery_init(){
	global $wpdb,$post_image_content;
	$attr = 'border="0" width="50" height="50"';
	$n_col = 2;
	$only_one_image = true;
	$verify_valid_link = false;
	$n_posts = 12;
	$image_limit = 7;
	$cache = false;
	$resize = false;
	$before = '<div id="post_image_gallery" style="text-align:center">';
	$after = '</div>';
	$width = 50;
	$height =50;
	$size_desc = 45;
	$default_title = 'Come and take a look';
	
	$c_images = 0;
	$div = '';
	$tooltip_js = '';
	while($c_images < $image_limit){
		$r = $wpdb->get_results('SELECT ID,post_title,post_content FROM '.$wpdb->prefix.'posts ORDER BY RAND() LIMIT '.$n_posts);
		foreach($r as $v){
			if($cache){
				if(file_exists(WP_PLUGIN_DIR.'/postimagegallery/cache/'.$v->ID)){
					$div .= '<a href="'.get_permalink($v->ID).'"><img src="'.WP_PLUGIN_URL.'/postimagegallery/cache/'.$v->ID.'" '.$attr.'></a>';
					$c_images++;
					continue;
				}
			}
			preg_match_all('!http://[a-z0-9\-\.\/]+\.(?:jpe?g|png|gif)!Ui',$v->post_content,$res);
			$srcs = $res[0];
			foreach($srcs as $k=>$src){
				if($verify_valid_link){
					if(@file_get_contents($src)==false) continue;
				}
				$title_desc = substr(str_replace("\t",'',ereg_replace("\n{2,}", '',ereg_replace(" {2,}", '',strip_tags($v->post_content)))),0,$size_desc);
				if(strlen($title_desc) < 10) $title_desc = $default_title;
				if($cache){
					if(file_exists(WP_PLUGIN_DIR.'/postimagegallery/cache/'.$v->ID)) 
						$src = WP_PLUGIN_URL.'/postimagegallery/cache/'.$v->ID;
				}
				$div .= '<a href="'.get_permalink($v->ID).'"><img title="'.$title_desc.'" src="'.$src.'" '.$attr.'></a>';
				
				//if(($c_images % $n_col)==0) echo '<br>';
				$c_images++;
				if($cache){
					if($resize){
						$out = WP_PLUGIN_DIR.'/postimagegallery/cache/'.$v->ID;
						$filename = $src;
						$im_info = @getimagesize($filename);
						if($im_info != false){
							$image_type = $image_info[2];
							
							$im_s = file_get_contents($src);
							$im_s = substr( $im_s, 0, strrpos( $im_s, '</iframe>' ) );
							$im = imagecreatefromstring($im_s);
							//if($im == false) continue;						  
							$new_image = imagecreatetruecolor($width, $height);
							@imagecopyresampled($new_image, $im, 0, 0, 0, 0, $width, $height, $im_info[0], $im_info[1]);
							$im = $new_image;
							if( $image_type == IMAGETYPE_JPEG ) 
								 imagejpeg($im,$out,75);
							elseif( $image_type == IMAGETYPE_GIF ) 
								 imagegif($im,$out);         
							elseif( $image_type == IMAGETYPE_PNG ) 
								 imagepng($im,$out);
					
									
						}else
							continue;
					
					}else{
						if(($cx = file_get_contents($src))==false) continue;
						else
					 		file_put_contents(WP_PLUGIN_DIR.'/postimagegallery/cache/'.$v->ID,$cx);
					}
				}
				if($only_one_image) break;
			}
			if($c_images == $image_limit) break;
		}
	}
	$post_image_content = $before . $div . $after;
}
function post_image_script(){
	post_image_gallery_init();
?>
	<script type="text/javascript">
	$(document).ready(function() {
		$("#post_image_gallery img[title]").tooltip();
	}
	</script>
<?php
}
function post_image_gallery_options_form(){
	
	?>
	<h3>How to use</h3>
	Edit Settings in post_image_gallery function above then place this code <pre>&lt;?php post_image_gallery(); ?&gt;</pre> where you
want the gallery.
	<span style="color:red">Currently options are disable, see post_image_gallery function</span>
	<table>
	<tr><td>Number of Columns</td><td><input type="text" name="columns" size="3"></td><td></td></tr>
	<tr><td>Number of Images</td><td><input type="text" name="n_images" size="3"></td><td></td></tr>
	<tr><td>Only Use First Image</td><td><input type="text" name="first_only" size="3"></td><td></td></tr>
	<tr><td>Image Attributes</td><td><input type="text" name="image_attr" size="3"></td><td>(* Use width="50" height="50")</td></tr>
	</table>
	<?php
}
function post_image_gallery_admin(){
	add_options_page('Post Image Options','Post Image Options','manage_options',__FILE__,'post_image_gallery_options_form');
}
add_action('admin_menu','post_image_gallery_admin');
add_action('wp_head', 'post_image_script');
?>