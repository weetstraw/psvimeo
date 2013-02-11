<?php
	error_reporting(E_ALL);
	/*
	Plugin Name: PS Vimeo Album
	Plugin URI: http://www.petersurrena.com
	Description: It's a Vimeo thing
	Version: 0.6
	Author: Peter Surrena
	Author URI: http://www.petersurrena.com
	*/
	
	class PS_VimeoAlbum extends WP_Widget {
		
		function __construct()
		{
			$params=array(
				'description' => 'Show a Vimeo Album',
				'name'		  => 'PS Vimeo Album'
			);
			
			parent::__construct('PS_VimeoAlbum','',$params);
		}
		
		public function form($instance)
		{
			extract($instance);

			// Title
			echo '<p><label for="'.$this->get_field_id('title').'">Title:</label>';
			echo '<input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'"';
			echo 'value="';
			if(isset($title)){
				echo esc_attr($title);
			}
			echo '"/></p>';
			
			// Album #
			echo '<p><label for="'.$this->get_field_id('album').'">Album #:</label>';
			echo '<input class="widefat" type="number" id="'.$this->get_field_id('album').'" name="'.$this->get_field_name('album').'" value="';
			echo !empty($album) ? $album : 2250271;
			echo '"/></p>';
			
			// Return Limit
			echo '<p><label for="'.$this->get_field_id('limit').'">Limit:</label>';
			echo '<input class="widefat" type="number" min="1" max="99" id="'.$this->get_field_id('limit').'" name="'.$this->get_field_name('limit').'" value="';
			echo !empty($limit) ? $limit : 9;
			echo '"/></p>';
			
			// Base Url
			echo '<p><label for="'.$this->get_field_id('baseurl').'">Base URL:</label>';
			echo '<input class="widefat" max="99" id="'.$this->get_field_id('baseurl').'" name="'.$this->get_field_name('baseurl').'" value="';
			echo !empty($baseurl) ? esc_attr($baseurl) : 'https://www.vimeo.com/';
			echo '"/></p>';
			
			// Cache
			echo '<p><label for="'.$this->get_field_id('cache').'">Cache: <em>In Minutes</em></label>';
			echo '<input class="widefat" min="1" max="9" id="'.$this->get_field_id('cache').'" name="'.$this->get_field_name('cache').'" value="';
			echo !empty($cache) ? $cache : '5';
			echo '"/></p>';
		}
		
		public function widget($args,$instance)
		{
			extract($args);
			extract($instance);
			
			if(empty($title)) $title="Videos";
			
			$data=$this->vimeo($album,$limit,$baseurl,$cache);
						
			if(false !== $data && isset($data->videos))
			{	
				echo $before_widget;
				echo $before_title.$title.$after_title;
				echo '<ul class="video_list">';
				
				foreach($data->videos as $row){
					echo '<li>';
					echo '<a href="'.$baseurl.$row['id'].'" title="'.$row['title'].'">';
					echo '<img class="video_icon" src="'.plugins_url('psvimeowidget/images/video-icon.png').'" alt="Play Buton" />';
					echo '</a>';
					echo '<img class="video_thumbnail" src="'.$row['thumb'].'" alt="'.$row['title'].'"/>';					
					echo '</li>';
				}

				echo '</ul>';
				echo $after_widget;
			}
		}
		
		private function vimeo($album,$limit,$baseurl,$cache)
		{
			if(empty($album)) return false;
			
			$videos=get_transient('recent_videos');
						
			if(!$videos || $videos->album != $album || $videos->limit != $limit || $videos->baseurl != $baseurl || $videos->cache != $cache){
				return $this->get_videos($album,$limit,$baseurl,$cache);
			}else{
				return $videos;
			}
		}
		
		private function get_videos($album,$limit,$baseurl,$cache)
		{
			$video_list=wp_remote_get('http://www.vimeo.com/api/v2/album/'.$album.'/videos.json');
			$video_list=json_decode($video_list['body']);
			
			if(isset($video_list->error)) return false;	
			
			$data=new stdClass();
			$data->album=$album;
			$data->limit=$limit;
			$data->baseurl=$baseurl;
			$data->cache=$cache;
			$data->videos=array();
			foreach($video_list as $video){
				if($limit-- ==0) break;
				$data->videos[]=array('id'=>$video->id,'title'=>$video->title,'thumb'=>$video->thumbnail_small);
			}	
	
			set_transient('recent_videos', $data, 60*$cache);

			return $data;			
		}
	}
	
	function ps_load_style()
	{
		wp_enqueue_style("psvimeo_style", plugin_dir_url( __FILE__ ).'psvimeo.css', array(), '0.1', 'screen' );
	}
	
	function ps_register_vimeo()
	{
		register_widget('PS_VimeoAlbum');
	}
	
	add_action('widgets_init','ps_register_vimeo');
	add_action('wp_enqueue_scripts','ps_load_style');	
?>