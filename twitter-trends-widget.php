<?php
/* 
Plugin Name: Twitter Trends Widget
Plugin URI: https://github.com/jetonr/twitter-trends-widget
Description: Displays Twitter Trends in WordPress widget from Countries and Cities.
Author: jetonr
Version: 1.0 
Author URI: http://jrwebstudio.com 
Donate link: http://goo.gl/RZiu34
*/  
    
add_action('widgets_init', 'trends_load_widgets');

function trends_load_widgets()
{
	register_widget('Twitter_Trends_Widget');
}

// Register style sheet.
add_action( 'wp_enqueue_scripts', 'twitter_trends_widget_styles' );

function twitter_trends_widget_styles() {
	wp_register_style( 'twitter-trends-widget', plugins_url( 'twitter-trends-widget/css/twitter_trends_widget.css' ) );
	wp_enqueue_style( 'twitter-trends-widget' );
}

class Twitter_Trends_Widget extends WP_Widget {

	function Twitter_Trends_Widget()
	{
		$widget_ops = array('description' => 'Shows latest trends from Cities and Countries');

		$control_ops = array('id_base' => 'twitter-trends-widget');

		$this->WP_Widget('twitter-trends-widget', 'Twitter Trends Widget', $widget_ops, $control_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$region = $instance['region'];
		$cacheTime = $instance['expiration'];
		$count = (int) $instance['count'];
		$consumer_key = $instance['consumer_key'];
		$consumer_secret = $instance['consumer_secret'];
		$access_token = $instance['access_token'];
		$access_token_secret = $instance['access_token_secret'];
		


		echo $before_widget;

		if($title) {
			echo $before_title.$title.$after_title;
		}

		if($region && $consumer_key && $consumer_secret && $access_token && $access_token_secret && $count) {
		$transName = 'twitter_trends_'.$args['widget_id'];

		if(false === ($twitterData = get_transient($transName))) {

			$token = get_option('cfTwitterToken');

			// getting new auth bearer only if we don't have one
			if(!$token) {
				// preparing credentials
				$credentials = $consumer_key . ':' . $consumer_secret;
				$toSend = base64_encode($credentials);

				// http post arguments
				$args = array(
					'method' => 'POST',
					'httpversion' => '1.1',
					'blocking' => true,
					'headers' => array(
						'Authorization' => 'Basic ' . $toSend,
						'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
					),
					'body' => array( 'grant_type' => 'client_credentials' )
				);

				add_filter('https_ssl_verify', '__return_false');
				$response = wp_remote_post('https://api.twitter.com/oauth2/token', $args);

				$keys = json_decode(wp_remote_retrieve_body($response));

				if($keys) {
					// saving token to wp_options table
					update_option('cfTwitterToken', $keys->access_token);
					$token = $keys->access_token;
				}
			}
			// we have bearer token wether we obtained it from API or from options
			$args = array(
				'httpversion' => '1.1',
				'blocking' => true,
				'headers' => array(
					'Authorization' => "Bearer $token"
				)
			);

			add_filter('https_ssl_verify', '__return_false');
	        $api_url = 'https://api.twitter.com/1.1/trends/place.json?id='.$region;
			$response = wp_remote_get($api_url, $args);
			$decoded_json = json_decode(wp_remote_retrieve_body($response), true);
			
			
			set_transient($transName, $decoded_json, 60*60*$cacheTime);
			
		}

		$twitter = (array) get_transient($transName);

		if($twitter && is_array($twitter)) {

		?>
        <div class="twitter-trends-widget clearfix" id="<?php echo $args['widget_id']; ?>">
            <div class="hashcloud">
              <?php 
              for ($i=0; $i < $count; $i++){ 
                echo '<a href='.$twitter[0]['trends'][$i]['url'].' target="_blank">'.$twitter[0]['trends'][$i]['name'].'</a>';
              }   
              ?>
            </div>
        </div>
		<?php }}

		echo $after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['region'] = $new_instance['region'];
		$instance['count'] = $new_instance['count'];
		$instance['expiration'] = $new_instance['expiration'];
		$instance['consumer_key'] = $new_instance['consumer_key'];
		$instance['consumer_secret'] = $new_instance['consumer_secret'];
		$instance['access_token'] = $new_instance['access_token'];
		$instance['access_token_secret'] = $new_instance['access_token_secret'];

		return $instance;
	}

	function form($instance)
	{
		$defaults = array(
			'title' => 'Twitter Trends', 
			'region' => '', 
			'count' => 20,
			'expiration' => '',  
			'consumer_key' => '', 
			'consumer_secret' => '', 
			'access_token' => '',
			'access_token_secret' => '',
			);
		$instance = wp_parse_args((array) $instance, $defaults); ?>

	<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" style="width: 216px;" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" />
	</p>
        <p>
          <label for="<?php echo $this->get_field_id( 'region' ); ?>"><?php _e( 'Select Region:' ); ?>
          <select  class="widefat" name="<?php echo $this->get_field_name( 'region' ); ?>">
          <option value="1" <?=$instance['region'] == '1' ? ' selected="selected"' : '';?>>Worldwide</option>
          <optgroup label="Countries">
          <option value="23424747" <?=$instance['region'] == '23424747' ? ' selected="selected"' : '';?>>Argentina</option>
          <option value="23424748" <?=$instance['region'] == '23424748' ? ' selected="selected"' : '';?>>Australia</option>
          <option value="23424757" <?=$instance['region'] == '23424757' ? ' selected="selected"' : '';?>>Belgium</option>
          <option value="23424768" <?=$instance['region'] == '23424768' ? ' selected="selected"' : '';?>>Brazil</option>
          <option value="23424775" <?=$instance['region'] == '23424775' ? ' selected="selected"' : '';?>>Canada</option>
          <option value="23424782" <?=$instance['region'] == '23424782' ? ' selected="selected"' : '';?>>Chile</option>
          <option value="23424787" <?=$instance['region'] == '23424787' ? ' selected="selected"' : '';?>>Colombia</option>
          <option value="23424800" <?=$instance['region'] == '23424800' ? ' selected="selected"' : '';?>>Dominican Republic</option>
          <option value="23424801" <?=$instance['region'] == '23424801' ? ' selected="selected"' : '';?>>Ecuador</option>
          <option value="23424819" <?=$instance['region'] == '23424819' ? ' selected="selected"' : '';?>>France</option>
          <option value="23424829" <?=$instance['region'] == '23424829' ? ' selected="selected"' : '';?>>Germany</option>
          <option value="23424833" <?=$instance['region'] == '23424833' ? ' selected="selected"' : '';?>>Greece</option>
          <option value="23424834" <?=$instance['region'] == '23424834' ? ' selected="selected"' : '';?>>Guatemala</option>
          <option value="23424848" <?=$instance['region'] == '23424848' ? ' selected="selected"' : '';?>>India</option>
          <option value="23424846" <?=$instance['region'] == '23424846' ? ' selected="selected"' : '';?>>Indonesia</option>
          <option value="23424803" <?=$instance['region'] == '23424803' ? ' selected="selected"' : '';?>>Ireland</option>
          <option value="23424853" <?=$instance['region'] == '23424853' ? ' selected="selected"' : '';?>>Italy</option>
          <option value="23424856" <?=$instance['region'] == '23424856' ? ' selected="selected"' : '';?>>Japan</option>
          <option value="23424863" <?=$instance['region'] == '23424863' ? ' selected="selected"' : '';?>>Kenya</option>
          <option value="23424868" <?=$instance['region'] == '23424868' ? ' selected="selected"' : '';?>>Korea</option>
          <option value="23424901" <?=$instance['region'] == '23424901' ? ' selected="selected"' : '';?>>Malaysia</option>
          <option value="23424900" <?=$instance['region'] == '23424900' ? ' selected="selected"' : '';?>>Mexico</option>
          <option value="23424909" <?=$instance['region'] == '23424909' ? ' selected="selected"' : '';?>>Netherlands</option>
          <option value="23424916" <?=$instance['region'] == '23424916' ? ' selected="selected"' : '';?>>New Zealand</option>
	      <option value="23424908" <?=$instance['region'] == '23424908' ? ' selected="selected"' : '';?>>Nigeria</option>
          <option value="23424910" <?=$instance['region'] == '23424910' ? ' selected="selected"' : '';?>>Norway</option>
          <option value="23424922" <?=$instance['region'] == '23424922' ? ' selected="selected"' : '';?>>Pakistan</option>
          <option value="23424934" <?=$instance['region'] == '23424934' ? ' selected="selected"' : '';?>>Philippines</option>
          <option value="23424923" <?=$instance['region'] == '23424923' ? ' selected="selected"' : '';?>>Poland</option>
          <option value="23424925" <?=$instance['region'] == '23424925' ? ' selected="selected"' : '';?>>Portugal</option>
          <option value="23424936" <?=$instance['region'] == '23424936' ? ' selected="selected"' : '';?>>Russia</option>
          <option value="23424948" <?=$instance['region'] == '23424948' ? ' selected="selected"' : '';?>>Singapore</option>
          <option value="23424942" <?=$instance['region'] == '23424942' ? ' selected="selected"' : '';?>>South Africa</option>
          <option value="23424950" <?=$instance['region'] == '23424950' ? ' selected="selected"' : '';?>>Spain</option>
          <option value="23424954" <?=$instance['region'] == '23424954' ? ' selected="selected"' : '';?>>Sweden</option>   
          <option value="23424969" <?=$instance['region'] == '23424969' ? ' selected="selected"' : '';?>>Turkey</option>
          <option value="23424976" <?=$instance['region'] == '23424976' ? ' selected="selected"' : '';?>>Ukraine</option>
          <option value="23424738" <?=$instance['region'] == '23424738' ? ' selected="selected"' : '';?>>United Arab Emirates</option>
          <option value="23424975" <?=$instance['region'] == '23424975' ? ' selected="selected"' : '';?>>United Kingdom</option>
          <option value="23424977" <?=$instance['region'] == '23424977' ? ' selected="selected"' : '';?>>United States</option>          
          <option value="23424982" <?=$instance['region'] == '23424982' ? ' selected="selected"' : '';?>>Venezuela</option>
          </optgroup>
          <optgroup label="Argentina">
            <option value="468739" <?=$instance['region'] == '468739' ? ' selected="selected"' : '';?>>Buenos Aires</option>
            <option value="466861" <?=$instance['region'] == '466861' ? ' selected="selected"' : '';?>>C&oacute;rdoba</option>
            <option value="332471" <?=$instance['region'] == '332471' ? ' selected="selected"' : '';?>>Mendoza</option>
            <option value="466862" <?=$instance['region'] == '466862' ? ' selected="selected"' : '';?>>Rosario</option>
          </optgroup>
          <optgroup label="Australia">
            <option value="1099805" <?=$instance['region'] == '1099805' ? ' selected="selected"' : '';?>>Adelaide</option>
            <option value="1100661" <?=$instance['region'] == '1100661' ? ' selected="selected"' : '';?>>Brisbane</option>
            <option value="1100968" <?=$instance['region'] == '1100968' ? ' selected="selected"' : '';?>>Canberra</option>
            <option value="1101597" <?=$instance['region'] == '1101597' ? ' selected="selected"' : '';?>>Darwin</option>
            <option value="1103816" <?=$instance['region'] == '1103816' ? ' selected="selected"' : '';?>>Melbourne</option>
            <option value="1098081" <?=$instance['region'] == '1098081' ? ' selected="selected"' : '';?>>Perth</option>
            <option value="1105779" <?=$instance['region'] == '1105779' ? ' selected="selected"' : '';?>>Sydney</option>
          </optgroup>
          <optgroup label="Brazil">
            <option value="455820" <?=$instance['region'] == '455820' ? ' selected="selected"' : '';?>>Bel&eacute;m</option>
            <option value="455821" <?=$instance['region'] == '455821' ? ' selected="selected"' : '';?>>Belo Horizonte</option>
            <option value="455819" <?=$instance['region'] == '455819' ? ' selected="selected"' : '';?>>Bras&iacute;lia</option>
            <option value="455828" <?=$instance['region'] == '455828' ? ' selected="selected"' : '';?>>Campinas</option>
            <option value="455822" <?=$instance['region'] == '455822' ? ' selected="selected"' : '';?>>Curitiba</option>
            <option value="455830" <?=$instance['region'] == '455830' ? ' selected="selected"' : '';?>>Fortaleza</option>
            <option value="455831" <?=$instance['region'] == '455831' ? ' selected="selected"' : '';?>>Goi&acirc;nia</option>
            <option value="455867" <?=$instance['region'] == '455867' ? ' selected="selected"' : '';?>>Guarulhos</option>
            <option value="455833" <?=$instance['region'] == '455833' ? ' selected="selected"' : '';?>>Manaus</option>
            <option value="455823" <?=$instance['region'] == '455823' ? ' selected="selected"' : '';?>>Porto Alegre</option>
            <option value="455824" <?=$instance['region'] == '455824' ? ' selected="selected"' : '';?>>Recife</option>
            <option value="455825" <?=$instance['region'] == '455825' ? ' selected="selected"' : '';?>>Rio de Janeiro</option>
            <option value="455826" <?=$instance['region'] == '455826' ? ' selected="selected"' : '';?>>Salvador</option>
            <option value="455834" <?=$instance['region'] == '455834' ? ' selected="selected"' : '';?>>S&atilde;o Lu&iacute;s</option>
            <option value="455827" <?=$instance['region'] == '455827' ? ' selected="selected"' : '';?>>S&atilde;o Paulo</option>
          </optgroup>
          <optgroup label="Canada">
            <option value="8775" <?=$instance['region'] == '8775' ? ' selected="selected"' : '';?>>Calgary</option>
            <option value="8676" <?=$instance['region'] == '8676' ? ' selected="selected"' : '';?>>Edmonton</option>
            <option value="3534" <?=$instance['region'] == '3534' ? ' selected="selected"' : '';?>>Montreal</option>
            <option value="3369" <?=$instance['region'] == '3369' ? ' selected="selected"' : '';?>>Ottawa</option>
            <option value="3444" <?=$instance['region'] == '3444' ? ' selected="selected"' : '';?>>Quebec</option>
            <option value="4118" <?=$instance['region'] == '4118' ? ' selected="selected"' : '';?>>Toronto</option>
            <option value="9807" <?=$instance['region'] == '9807' ? ' selected="selected"' : '';?>>Vancouver</option>
            <option value="2972" <?=$instance['region'] == '2972' ? ' selected="selected"' : '';?>>Winnipeg</option>
          </optgroup>
          <optgroup label="Chile">
            <option value="349860" <?=$instance['region'] == '349860' ? ' selected="selected"' : '';?>>Concepcion</option>
            <option value="349859" <?=$instance['region'] == '349859' ? ' selected="selected"' : '';?>>Santiago</option>
            <option value="349861" <?=$instance['region'] == '349861' ? ' selected="selected"' : '';?>>Valparaiso</option>
          </optgroup>
          <optgroup label="Colombia">
            <option value="368151" <?=$instance['region'] == '368151' ? ' selected="selected"' : '';?>>Barranquilla</option>
            <option value="368148" <?=$instance['region'] == '368148' ? ' selected="selected"' : '';?>>Bogot&aacute;</option>
            <option value="368149" <?=$instance['region'] == '368149' ? ' selected="selected"' : '';?>>Cali</option>
            <option value="368150" <?=$instance['region'] == '368150' ? ' selected="selected"' : '';?>>Medell&iacute;n</option>
          </optgroup>
          <optgroup label="Ecuador">
            <option value="375733" <?=$instance['region'] == '375733' ? ' selected="selected"' : '';?>>Guayaquil</option>
            <option value="375732" <?=$instance['region'] == '375732' ? ' selected="selected"' : '';?>>Quito</option>
          </optgroup>
          <optgroup label="France">      
            <option value="580778" <?=$instance['region'] == '580778' ? ' selected="selected"' : '';?>>Bordeaux</option>
            <option value="608105" <?=$instance['region'] == '608105' ? ' selected="selected"' : '';?>>Lille</option>
            <option value="609125" <?=$instance['region'] == '609125' ? ' selected="selected"' : '';?>>Lyon</option>
            <option value="610264" <?=$instance['region'] == '610264' ? ' selected="selected"' : '';?>>Marseille</option>
            <option value="612977" <?=$instance['region'] == '612977' ? ' selected="selected"' : '';?>>Montpellier</option>
            <option value="613858" <?=$instance['region'] == '613858' ? ' selected="selected"' : '';?>>Nantes</option>
            <option value="615702" <?=$instance['region'] == '615702' ? ' selected="selected"' : '';?>>Paris</option>
            <option value="619163" <?=$instance['region'] == '619163' ? ' selected="selected"' : '';?>>Rennes</option>
            <option value="627791" <?=$instance['region'] == '627791' ? ' selected="selected"' : '';?>>Strasbourg</option>
            <option value="628886" <?=$instance['region'] == '628886' ? ' selected="selected"' : '';?>>Toulouse</option>
          </optgroup>
          <optgroup label="Germany"> 
            <option value="638242" <?=$instance['region'] == '638242' ? ' selected="selected"' : '';?>>Berlin</option>
            <option value="641142" <?=$instance['region'] == '641142' ? ' selected="selected"' : '';?>>Bremen</option>
            <option value="667931" <?=$instance['region'] == '667931' ? ' selected="selected"' : '';?>>Cologne</option>
            <option value="645458" <?=$instance['region'] == '645458' ? ' selected="selected"' : '';?>>Dortmund</option>
            <option value="645686" <?=$instance['region'] == '645686' ? ' selected="selected"' : '';?>>Dresden</option>
            <option value="646099" <?=$instance['region'] == '646099' ? ' selected="selected"' : '';?>>Dusseldorf</option>
            <option value="648820" <?=$instance['region'] == '648820' ? ' selected="selected"' : '';?>>Essen</option>
            <option value="650272" <?=$instance['region'] == '650272' ? ' selected="selected"' : '';?>>Frankfurt</option>
            <option value="656958" <?=$instance['region'] == '656958' ? ' selected="selected"' : '';?>>Hamburg</option>
            <option value="671072" <?=$instance['region'] == '671072' ? ' selected="selected"' : '';?>>Leipzig</option>
            <option value="676757" <?=$instance['region'] == '676757' ? ' selected="selected"' : '';?>>Munich</option>
            <option value="698064" <?=$instance['region'] == '698064' ? ' selected="selected"' : '';?>>Stuttgart</option>
            </optgroup>
          <optgroup label="Greece"> 
            <option value="946738" <?=$instance['region'] == '946738' ? ' selected="selected"' : '';?>>Athens</option>
            <option value="963291" <?=$instance['region'] == '963291' ? ' selected="selected"' : '';?>>Thessaloniki</option>
          </optgroup>
          <optgroup label="India"> 
            <option value="2295402" <?=$instance['region'] == '2295402' ? ' selected="selected"' : '';?>>Ahmedabad</option>
            <option value="2295388" <?=$instance['region'] == '2295388' ? ' selected="selected"' : '';?>>Amritsar</option>
            <option value="2295420" <?=$instance['region'] == '2295420' ? ' selected="selected"' : '';?>>Bangalore</option>
            <option value="2295407" <?=$instance['region'] == '2295407' ? ' selected="selected"' : '';?>>Bhopal</option>
            <option value="2295424" <?=$instance['region'] == '2295424' ? ' selected="selected"' : '';?>>Chennai</option>
            <option value="20070458" <?=$instance['region'] == '20070458' ? ' selected="selected"' : '';?>>Delhi</option>
            <option value="2295414" <?=$instance['region'] == '2295414' ? ' selected="selected"' : '';?>>Hyderabad</option>
            <option value="2295408" <?=$instance['region'] == '2295408' ? ' selected="selected"' : '';?>>Indore</option>
            <option value="2295401" <?=$instance['region'] == '2295401' ? ' selected="selected"' : '';?>>Jaipur</option>
            <option value="2295378" <?=$instance['region'] == '2295378' ? ' selected="selected"' : '';?>>Kanpur</option>
            <option value="2295386" <?=$instance['region'] == '2295386' ? ' selected="selected"' : '';?>>Kolkata</option>
            <option value="2295377" <?=$instance['region'] == '2295377' ? ' selected="selected"' : '';?>>Lucknow</option>
            <option value="2295411" <?=$instance['region'] == '2295411' ? ' selected="selected"' : '';?>>Mumbai</option>
            <option value="2282863" <?=$instance['region'] == '2282863' ? ' selected="selected"' : '';?>>Nagpur</option>
            <option value="2295381" <?=$instance['region'] == '2295381' ? ' selected="selected"' : '';?>>Patna</option>
            <option value="2295412" <?=$instance['region'] == '2295412' ? ' selected="selected"' : '';?>>Pune</option>
            <option value="2295404" <?=$instance['region'] == '2295404' ? ' selected="selected"' : '';?>>Rajkot</option>
            <option value="2295383" <?=$instance['region'] == '2295383' ? ' selected="selected"' : '';?>>Ranchi</option>
            <option value="2295387" <?=$instance['region'] == '2295387' ? ' selected="selected"' : '';?>>Srinagar</option>
            <option value="2295405" <?=$instance['region'] == '2295405' ? ' selected="selected"' : '';?>>Surat</option>
            <option value="2295410" <?=$instance['region'] == '2295410' ? ' selected="selected"' : '';?>>Thane</option>
          </optgroup>
          <optgroup label="Indonesia"> 
            <option value="1047180" <?=$instance['region'] == '1047180' ? ' selected="selected"' : '';?>>Bandung</option>
            <option value="1030077" <?=$instance['region'] == '1030077' ? ' selected="selected"' : '';?>>Bekasi</option>
            <option value="1032539" <?=$instance['region'] == '1032539' ? ' selected="selected"' : '';?>>Depok</option>
            <option value="1047378" <?=$instance['region'] == '1047378' ? ' selected="selected"' : '';?>>Jakarta</option>
            <option value="1046138" <?=$instance['region'] == '1046138' ? ' selected="selected"' : '';?>>Makassar</option>
            <option value="1047908" <?=$instance['region'] == '1047908' ? ' selected="selected"' : '';?>>Medan</option>
            <option value="1048059" <?=$instance['region'] == '1048059' ? ' selected="selected"' : '';?>>Palembang</option>
            <option value="1040779" <?=$instance['region'] == '1040779' ? ' selected="selected"' : '';?>>Pekanbaru</option>
            <option value="1048324" <?=$instance['region'] == '1048324' ? ' selected="selected"' : '';?>>Semarang</option>
            <option value="1044316" <?=$instance['region'] == '1044316' ? ' selected="selected"' : '';?>>Surabaya</option>
            <option value="1048536" <?=$instance['region'] == '1048536' ? ' selected="selected"' : '';?>>Tangerang</option>
          </optgroup>
          <optgroup label="Ireland"> 
          <option value="560472" <?=$instance['region'] == '560472' ? ' selected="selected"' : '';?>>Cork</option>
          <option value="560743" <?=$instance['region'] == '560743' ? ' selected="selected"' : '';?>>Dublin</option>
          <option value="560912" <?=$instance['region'] == '560912' ? ' selected="selected"' : '';?>>Galway</option>
          </optgroup>
          <optgroup label="Italy"> 
          <option value="711080" <?=$instance['region'] == '711080' ? ' selected="selected"' : '';?>>Bologna</option>
          <option value="716085" <?=$instance['region'] == '716085' ? ' selected="selected"' : '';?>>Genoa</option>
          <option value="718345" <?=$instance['region'] == '718345' ? ' selected="selected"' : '';?>>Milan</option>
          <option value="719258" <?=$instance['region'] == '719258' ? ' selected="selected"' : '';?>>Naples</option>
          <option value="719846" <?=$instance['region'] == '719846' ? ' selected="selected"' : '';?>>Palermo</option>
          <option value="721943" <?=$instance['region'] == '721943' ? ' selected="selected"' : '';?>>Rome</option>
          <option value="725003" <?=$instance['region'] == '725003' ? ' selected="selected"' : '';?>>Turin</option>
          </optgroup>
          <optgroup label="Japan"> 
          <option value="1117034" <?=$instance['region'] == '1117034' ? ' selected="selected"' : '';?>>Chiba</option>
          <option value="1117099" <?=$instance['region'] == '1117099' ? ' selected="selected"' : '';?>>Fukuoka</option>
          <option value="1117155" <?=$instance['region'] == '1117155' ? ' selected="selected"' : '';?>>Hamamatsu</option>
          <option value="1117227" <?=$instance['region'] == '1117227' ? ' selected="selected"' : '';?>>Hiroshima</option>
          <option value="1117502" <?=$instance['region'] == '1117502' ? ' selected="selected"' : '';?>>Kawasaki</option>
          <option value="1110809" <?=$instance['region'] == '1110809' ? ' selected="selected"' : '';?>>Kitakyushu</option>
          <option value="1117545" <?=$instance['region'] == '1117545' ? ' selected="selected"' : '';?>>Kobe</option>
          <option value="1117605" <?=$instance['region'] == '1117605' ? ' selected="selected"' : '';?>>Kumamoto</option>
          <option value="15015372" <?=$instance['region'] == '15015372' ? ' selected="selected"' : '';?>>Kyoto</option>
          <option value="1117817" <?=$instance['region'] == '1117817' ? ' selected="selected"' : '';?>>Nagoya</option>
          <option value="1117881" <?=$instance['region'] == '1117881' ? ' selected="selected"' : '';?>>Niigata</option>
          <option value="90036018" <?=$instance['region'] == '90036018' ? ' selected="selected"' : '';?>>Okayama</option>
          <option value="2345896" <?=$instance['region'] == '2345896' ? ' selected="selected"' : '';?>>Okinawa</option>
          <option value="15015370" <?=$instance['region'] == '15015370' ? ' selected="selected"' : '';?>>Osaka</option>
          <option value="1118072" <?=$instance['region'] == '1118072' ? ' selected="selected"' : '';?>>Sagamihara</option>
          <option value="1116753" <?=$instance['region'] == '1116753' ? ' selected="selected"' : '';?>>Saitama</option>
          <option value="1118108" <?=$instance['region'] == '1118108' ? ' selected="selected"' : '';?>>Sapporo</option>
          <option value="1118129" <?=$instance['region'] == '1118129' ? ' selected="selected"' : '';?>>Sendai</option>
          <option value="1118285" <?=$instance['region'] == '1118285' ? ' selected="selected"' : '';?>>Takamatsu</option>
          <option value="1118370" <?=$instance['region'] == '1118370' ? ' selected="selected"' : '';?>>Tokyo</option>
          <option value="1118550" <?=$instance['region'] == '1118550' ? ' selected="selected"' : '';?>>Yokohama</option>
          </optgroup>
          <optgroup label="Kenya"> 
          <option value="1528335" <?=$instance['region'] == '1528335' ? ' selected="selected"' : '';?>>Mombasa</option>
          <option value="1528488" <?=$instance['region'] == '1528488' ? ' selected="selected"' : '';?>>Nairobi</option>
          </optgroup>
          <optgroup label="Korea"> 
          <option value="1132444" <?=$instance['region'] == '1132444' ? ' selected="selected"' : '';?>>Ansan</option>
          <option value="1132445" <?=$instance['region'] == '1132445' ? ' selected="selected"' : '';?>>Bucheon</option>
          <option value="1132447" <?=$instance['region'] == '1132447' ? ' selected="selected"' : '';?>>Busan</option>
          <option value="1132449" <?=$instance['region'] == '1132449' ? ' selected="selected"' : '';?>>Changwon</option>
          <option value="1132466" <?=$instance['region'] == '1132466' ? ' selected="selected"' : '';?>>Daegu</option>
          <option value="2345975" <?=$instance['region'] == '2345975' ? ' selected="selected"' : '';?>>Daejeon</option>
          <option value="1130853" <?=$instance['region'] == '1130853' ? ' selected="selected"' : '';?>>Goyang</option>
          <option value="1132481" <?=$instance['region'] == '1132481' ? ' selected="selected"' : '';?>>Gwangju</option>
          <option value="1132496" <?=$instance['region'] == '1132496' ? ' selected="selected"' : '';?>>Incheon</option>
          <option value="1132559" <?=$instance['region'] == '1132559' ? ' selected="selected"' : '';?>>Seongnam</option>
          <option value="1132599" <?=$instance['region'] == '1132599' ? ' selected="selected"' : '';?>>Seoul</option>
          <option value="1132567" <?=$instance['region'] == '1132567' ? ' selected="selected"' : '';?>>Suwon</option>
          <option value="1132578" <?=$instance['region'] == '1132578' ? ' selected="selected"' : '';?>>Ulsan</option>
          <option value="1132094" <?=$instance['region'] == '1132094' ? ' selected="selected"' : '';?>>Yongin</option>
          </optgroup>
          <optgroup label="Malaysia">
          <option value="56013645" <?=$instance['region'] == '56013645' ? ' selected="selected"' : '';?>>Hulu Langat</option>
          <option value="1154679" <?=$instance['region'] == '1154679' ? ' selected="selected"' : '';?>>Ipoh</option>
          <option value="1154698" <?=$instance['region'] == '1154698' ? ' selected="selected"' : '';?>>Johor Bahru</option>
          <option value="1141268" <?=$instance['region'] == '1141268' ? ' selected="selected"' : '';?>>Kajang</option>
          <option value="1154726" <?=$instance['region'] == '1154726' ? ' selected="selected"' : '';?>>Klang</option>
          <option value="1154781" <?=$instance['region'] == '1154781' ? ' selected="selected"' : '';?>>Kuala Lumpur</option>
          <option value="56013632" <?=$instance['region'] == '56013632' ? ' selected="selected"' : '';?>>Petaling</option>
          </optgroup>
          <optgroup label="Mexico">
          <option value="110978" <?=$instance['region'] == '110978' ? ' selected="selected"' : '';?>>Acapulco</option>
          <option value="111579" <?=$instance['region'] == '111579' ? ' selected="selected"' : '';?>>Aguascalientes</option>
          <option value="115958" <?=$instance['region'] == '115958' ? ' selected="selected"' : '';?>>Chihuahua</option>
          <option value="116556" <?=$instance['region'] == '116556' ? ' selected="selected"' : '';?>>Ciudad Juarez</option>
          <option value="117994" <?=$instance['region'] == '117994' ? ' selected="selected"' : '';?>>Culiac&aacute;n</option>
          <option value="118466" <?=$instance['region'] == '118466' ? ' selected="selected"' : '';?>>Ecatepec de Morelos</option>
          <option value="124162" <?=$instance['region'] == '124162' ? ' selected="selected"' : '';?>>Guadalajara</option>
          <option value="124785" <?=$instance['region'] == '124785' ? ' selected="selected"' : '';?>>Hermosillo</option>
          <option value="131068" <?=$instance['region'] == '131068' ? ' selected="selected"' : '';?>>Le&oacute;n</option>
          <option value="133475" <?=$instance['region'] == '133475' ? ' selected="selected"' : '';?>>Mexicali</option>
          <option value="116545" <?=$instance['region'] == '116545' ? ' selected="selected"' : '';?>>Mexico City</option>
          <option value="134047" <?=$instance['region'] == '134047' ? ' selected="selected"' : '';?>>Monterrey</option>
          <option value="134091" <?=$instance['region'] == '134091' ? ' selected="selected"' : '';?>>Morelia</option>
          <option value="133327" <?=$instance['region'] == '133327' ? ' selected="selected"' : '';?>>M&eacute;rida</option>
          <option value="134395" <?=$instance['region'] == '134395' ? ' selected="selected"' : '';?>>Naucalpan de Ju&aacute;rez</option>
          <option value="116564" <?=$instance['region'] == '116564' ? ' selected="selected"' : '';?>>Nezahualc&oacute;yotl</option>
          <option value="137612" <?=$instance['region'] == '137612' ? ' selected="selected"' : '';?>>Puebla</option>
          <option value="138045" <?=$instance['region'] == '138045' ? ' selected="selected"' : '';?>>Quer&eacute;taro</option>
          <option value="141272" <?=$instance['region'] == '141272' ? ' selected="selected"' : '';?>>Saltillo</option>
          <option value="144265" <?=$instance['region'] == '144265' ? ' selected="selected"' : '';?>>San Luis Potos&iacute;</option>
          <option value="149361" <?=$instance['region'] == '149361' ? ' selected="selected"' : '';?>>Tijuana</option>
          <option value="149769" <?=$instance['region'] == '149769' ? ' selected="selected"' : '';?>>Toluca</option>
          <option value="151582" <?=$instance['region'] == '151582' ? ' selected="selected"' : '';?>>Zapopan</option>
          </optgroup>
          <optgroup label="Netherlands">
          <option value="727232" <?=$instance['region'] == '727232' ? ' selected="selected"' : '';?>>Amsterdam</option>
          <option value="726874" <?=$instance['region'] == '726874' ? ' selected="selected"' : '';?>>Den Haag</option>
          <option value="733075" <?=$instance['region'] == '733075' ? ' selected="selected"' : '';?>>Rotterdam</option>
          <option value="734047" <?=$instance['region'] == '734047' ? ' selected="selected"' : '';?>>Utrecht</option>
          </optgroup>
          <optgroup label="Nigeria">
          <option value="1387660" <?=$instance['region'] == '1387660' ? ' selected="selected"' : '';?>>Benin City</option>
          <option value="1393672" <?=$instance['region'] == '1393672' ? ' selected="selected"' : '';?>>Ibadan</option>
          <option value="1396439" <?=$instance['region'] == '1396439' ? ' selected="selected"' : '';?>>Kaduna</option>
          <option value="1396803" <?=$instance['region'] == '1396803' ? ' selected="selected"' : '';?>>Kano</option>
          <option value="1398823" <?=$instance['region'] == '1398823' ? ' selected="selected"' : '';?>>Lagos</option>
          <option value="1404447" <?=$instance['region'] == '1404447' ? ' selected="selected"' : '';?>>Port Harcourt</option>
          </optgroup>
          <optgroup label="Norway">
          <option value="857105" <?=$instance['region'] == '857105' ? ' selected="selected"' : '';?>>Bergen</option>
          <option value="862592" <?=$instance['region'] == '862592' ? ' selected="selected"' : '';?>>Oslo</option>
          </optgroup>
          <optgroup label="Pakistan">
          <option value="2211574" <?=$instance['region'] == '2211574' ? ' selected="selected"' : '';?>>Faisalabad</option>
          <option value="2211096" <?=$instance['region'] == '2211096' ? ' selected="selected"' : '';?>>Karachi</option>
          <option value="2211177" <?=$instance['region'] == '2211177' ? ' selected="selected"' : '';?>>Lahore</option>
          <option value="2211269" <?=$instance['region'] == '2211269' ? ' selected="selected"' : '';?>>Multan</option>
          <option value="2211387" <?=$instance['region'] == '2211387' ? ' selected="selected"' : '';?>>Rawalpindi</option>
          </optgroup>
          <option value="23424919" <?=$instance['region'] == '23424919' ? ' selected="selected"' : '';?>>Peru</option>
          <optgroup label="Philippines">
          <option value="1198785" <?=$instance['region'] == '1198785' ? ' selected="selected"' : '';?>>Antipolo</option>
          <option value="1199002" <?=$instance['region'] == '1199002' ? ' selected="selected"' : '';?>>Cagayan de Oro</option>
          <option value="1167715" <?=$instance['region'] == '1167715' ? ' selected="selected"' : '';?>>Calocan</option>
          <option value="1199079" <?=$instance['region'] == '1199079' ? ' selected="selected"' : '';?>>Cebu City</option>
          <option value="1199136" <?=$instance['region'] == '1199136' ? ' selected="selected"' : '';?>>Davao City</option>
          <option value="1180689" <?=$instance['region'] == '1180689' ? ' selected="selected"' : '';?>>Makati</option>
          <option value="1199477" <?=$instance['region'] == '1199477' ? ' selected="selected"' : '';?>>Manila</option>
          <option value="1187115" <?=$instance['region'] == '1187115' ? ' selected="selected"' : '';?>>Pasig</option>
          <option value="1199682" <?=$instance['region'] == '1199682' ? ' selected="selected"' : '';?>>Quezon City</option>
          <option value="1195098" <?=$instance['region'] == '1195098' ? ' selected="selected"' : '';?>>Taguig</option>
          <option value="1199980" <?=$instance['region'] == '1199980' ? ' selected="selected"' : '';?>>Zamboanga City</option>
          </optgroup>
          <optgroup label="Poland">
          <option value="493417" <?=$instance['region'] == '493417' ? ' selected="selected"' : '';?>>Gdańsk</option>
          <option value="502075" <?=$instance['region'] == '502075' ? ' selected="selected"' : '';?>>Krak&oacute;w</option>
          <option value="505120" <?=$instance['region'] == '505120' ? ' selected="selected"' : '';?>>Lodz</option>
          <option value="514048" <?=$instance['region'] == '514048' ? ' selected="selected"' : '';?>>Poznań</option>
          <option value="523920" <?=$instance['region'] == '523920' ? ' selected="selected"' : '';?>>Warsaw</option>
          <option value="526363" <?=$instance['region'] == '526363' ? ' selected="selected"' : '';?>>Wroclaw</option>
          </optgroup>
          <optgroup label="Russia">
          <option value="1997422" <?=$instance['region'] == '1997422' ? ' selected="selected"' : '';?>>Chelyabinsk</option>
          <option value="2121040" <?=$instance['region'] == '2121040' ? ' selected="selected"' : '';?>>Irkutsk</option>
          <option value="2121267" <?=$instance['region'] == '2121267' ? ' selected="selected"' : '';?>>Kazan</option>
          <option value="2018708" <?=$instance['region'] == '2018708' ? ' selected="selected"' : '';?>>Khabarovsk</option>
          <option value="2028717" <?=$instance['region'] == '2028717' ? ' selected="selected"' : '';?>>Krasnodar</option>
          <option value="2029043" <?=$instance['region'] == '2029043' ? ' selected="selected"' : '';?>>Krasnoyarsk</option>
          <option value="2122265" <?=$instance['region'] == '2122265' ? ' selected="selected"' : '';?>>Moscow</option>
          <option value="2122471" <?=$instance['region'] == '2122471' ? ' selected="selected"' : '';?>>Nizhny Novgorod</option>
          <option value="2122541" <?=$instance['region'] == '2122541' ? ' selected="selected"' : '';?>>Novosibirsk</option>
          <option value="2122641" <?=$instance['region'] == '2122641' ? ' selected="selected"' : '';?>>Omsk</option>
          <option value="2122814" <?=$instance['region'] == '2122814' ? ' selected="selected"' : '';?>>Perm</option>
          <option value="2123177" <?=$instance['region'] == '2123177' ? ' selected="selected"' : '';?>>Rostov-on-Don</option>
          <option value="2123260" <?=$instance['region'] == '2123260' ? ' selected="selected"' : '';?>>Saint Petersburg</option>
          <option value="2077746" <?=$instance['region'] == '2077746' ? ' selected="selected"' : '';?>>Samara</option>
          <option value="2124045" <?=$instance['region'] == '2124045' ? ' selected="selected"' : '';?>>Ufa</option>
          <option value="2124288" <?=$instance['region'] == '2124288' ? ' selected="selected"' : '';?>>Vladivostok</option>
          <option value="2124298" <?=$instance['region'] == '2124298' ? ' selected="selected"' : '';?>>Volgograd</option>
          <option value="2108210" <?=$instance['region'] == '2108210' ? ' selected="selected"' : '';?>>Voronezh</option>
          <option value="2112237" <?=$instance['region'] == '2112237' ? ' selected="selected"' : '';?>>Yekaterinburg</option>
          </optgroup>
          <optgroup label="South Africa">
          <option value="1591691" <?=$instance['region'] == '1591691' ? ' selected="selected"' : '';?>>Cape Town</option>
          <option value="1580913" <?=$instance['region'] == '1580913' ? ' selected="selected"' : '';?>>Durban</option>
          <option value="1582504" <?=$instance['region'] == '1582504' ? ' selected="selected"' : '';?>>Johannesburg</option>
          <option value="1586614" <?=$instance['region'] == '1586614' ? ' selected="selected"' : '';?>>Port Elizabeth</option>
          <option value="1586638" <?=$instance['region'] == '1586638' ? ' selected="selected"' : '';?>>Pretoria</option>
          <option value="1587677" <?=$instance['region'] == '1587677' ? ' selected="selected"' : '';?>>Soweto</option>
          </optgroup>
          <optgroup label="Spain">
          <option value="753692" <?=$instance['region'] == '753692' ? ' selected="selected"' : '';?>>Barcelona</option>
          <option value="754542" <?=$instance['region'] == '754542' ? ' selected="selected"' : '';?>>Bilbao</option>
          <option value="764814" <?=$instance['region'] == '764814' ? ' selected="selected"' : '';?>>Las Palmas</option>
          <option value="766273" <?=$instance['region'] == '766273' ? ' selected="selected"' : '';?>>Madrid</option>
          <option value="766356" <?=$instance['region'] == '766356' ? ' selected="selected"' : '';?>>Malaga</option>
          <option value="768026" <?=$instance['region'] == '768026' ? ' selected="selected"' : '';?>>Murcia</option>
          <option value="769293" <?=$instance['region'] == '769293' ? ' selected="selected"' : '';?>>Palma</option>
          <option value="774508" <?=$instance['region'] == '774508' ? ' selected="selected"' : '';?>>Seville</option>
          <option value="776688" <?=$instance['region'] == '776688' ? ' selected="selected"' : '';?>>Valencia</option>
          <option value="779063" <?=$instance['region'] == '779063' ? ' selected="selected"' : '';?>>Zaragoza</option>
          </optgroup>
          <optgroup label="Sweden">
          <option value="890869" <?=$instance['region'] == '890869' ? ' selected="selected"' : '';?>>Gothenburg</option>
          <option value="906057" <?=$instance['region'] == '906057' ? ' selected="selected"' : '';?>>Stockholm</option>
          </optgroup>
          <optgroup label="Turkey">
          <option value="2343678" <?=$instance['region'] == '2343678' ? ' selected="selected"' : '';?>>Adana</option>
          <option value="2343732" <?=$instance['region'] == '2343732' ? ' selected="selected"' : '';?>>Ankara</option>
          <option value="2343733" <?=$instance['region'] == '2343733' ? ' selected="selected"' : '';?>>Antalya</option>
          <option value="2343843" <?=$instance['region'] == '2343843' ? ' selected="selected"' : '';?>>Bursa</option>
          <option value="2343932" <?=$instance['region'] == '2343932' ? ' selected="selected"' : '';?>>Diyarbakır</option>
          <option value="2343980" <?=$instance['region'] == '2343980' ? ' selected="selected"' : '';?>>Eskişehir</option>
          <option value="2343999" <?=$instance['region'] == '2343999' ? ' selected="selected"' : '';?>>Gaziantep</option>
          <option value="2344116" <?=$instance['region'] == '2344116' ? ' selected="selected"' : '';?>>Istanbul</option>
          <option value="2344117" <?=$instance['region'] == '2344117' ? ' selected="selected"' : '';?>>Izmir</option>
          <option value="2344174" <?=$instance['region'] == '2344174' ? ' selected="selected"' : '';?>>Kayseri</option>
          <option value="2344210" <?=$instance['region'] == '2344210' ? ' selected="selected"' : '';?>>Konya</option>
          <option value="2323778" <?=$instance['region'] == '2323778' ? ' selected="selected"' : '';?>>Mersin</option>
          </optgroup>
          <optgroup label="Ukraine">
          <option value="918981" <?=$instance['region'] == '918981' ? ' selected="selected"' : '';?>>Dnipropetrovsk</option>
          <option value="919163" <?=$instance['region'] == '919163' ? ' selected="selected"' : '';?>>Donetsk</option>
          <option value="922137" <?=$instance['region'] == '922137' ? ' selected="selected"' : '';?>>Kharkiv</option>
          <option value="924938" <?=$instance['region'] == '924938' ? ' selected="selected"' : '';?>>Kyiv</option>
          <option value="924943" <?=$instance['region'] == '924943' ? ' selected="selected"' : '';?>>Lviv</option>
          <option value="929398" <?=$instance['region'] == '929398' ? ' selected="selected"' : '';?>>Odesa</option>
          <option value="939628" <?=$instance['region'] == '939628' ? ' selected="selected"' : '';?>>Zaporozhye</option>
          </optgroup>
          <optgroup label="United Arab Emirates">
          <option value="1940330" <?=$instance['region'] == '1940330' ? ' selected="selected"' : '';?>>Abu Dhabi</option>
          <option value="1940345" <?=$instance['region'] == '1940345' ? ' selected="selected"' : '';?>>Dubai</option>
          <option value="1940119" <?=$instance['region'] == '1940119' ? ' selected="selected"' : '';?>>Sharjah</option>
          </optgroup>
          <optgroup label="United Kingdom">
          <option value="44544" <?=$instance['region'] == '44544' ? ' selected="selected"' : '';?>>Belfast</option>
          <option value="12723" <?=$instance['region'] == '12723' ? ' selected="selected"' : '';?>>Birmingham</option>
          <option value="12903" <?=$instance['region'] == '12903' ? ' selected="selected"' : '';?>>Blackpool</option>
          <option value="13383" <?=$instance['region'] == '13383' ? ' selected="selected"' : '';?>>Bournemouth</option>
          <option value="13911" <?=$instance['region'] == '13911' ? ' selected="selected"' : '';?>>Brighton</option>
          <option value="13963" <?=$instance['region'] == '13963' ? ' selected="selected"' : '';?>>Bristol</option>
          <option value="15127" <?=$instance['region'] == '15127' ? ' selected="selected"' : '';?>>Cardiff</option>
          <option value="17044" <?=$instance['region'] == '17044' ? ' selected="selected"' : '';?>>Coventry</option>
          <option value="18114" <?=$instance['region'] == '18114' ? ' selected="selected"' : '';?>>Derby</option>
          <option value="19344" <?=$instance['region'] == '19344' ? ' selected="selected"' : '';?>>Edinburgh</option>
          <option value="21125" <?=$instance['region'] == '21125' ? ' selected="selected"' : '';?>>Glasgow</option>
          <option value="25211" <?=$instance['region'] == '25211' ? ' selected="selected"' : '';?>>Hull</option>
          <option value="26042" <?=$instance['region'] == '26042' ? ' selected="selected"' : '';?>>Leeds</option>
          <option value="26062" <?=$instance['region'] == '26062' ? ' selected="selected"' : '';?>>Leicester</option>
          <option value="26734" <?=$instance['region'] == '26734' ? ' selected="selected"' : '';?>>Liverpool</option>
          <option value="44418" <?=$instance['region'] == '44418' ? ' selected="selected"' : '';?>>London</option>
          <option value="28218" <?=$instance['region'] == '28218' ? ' selected="selected"' : '';?>>Manchester</option>
          <option value="28869" <?=$instance['region'] == '28869' ? ' selected="selected"' : '';?>>Middlesbrough</option>
          <option value="30079" <?=$instance['region'] == '30079' ? ' selected="selected"' : '';?>>Newcastle</option>
          <option value="30720" <?=$instance['region'] == '30720' ? ' selected="selected"' : '';?>>Nottingham</option>
          <option value="32185" <?=$instance['region'] == '32185' ? ' selected="selected"' : '';?>>Plymouth</option>
          <option value="32452" <?=$instance['region'] == '32452' ? ' selected="selected"' : '';?>>Portsmouth</option>
          <option value="32566" <?=$instance['region'] == '32566' ? ' selected="selected"' : '';?>>Preston</option>
          <option value="34503" <?=$instance['region'] == '34503' ? ' selected="selected"' : '';?>>Sheffield</option>
          <option value="36240" <?=$instance['region'] == '36240' ? ' selected="selected"' : '';?>>Stoke-on-Trent</option>
          <option value="36758" <?=$instance['region'] == '36758' ? ' selected="selected"' : '';?>>Swansea</option>
          </optgroup>
          <optgroup label="United States">
          <option value="2352824" <?=$instance['region'] == '2352824' ? ' selected="selected"' : '';?>>Albuquerque</option>
          <option value="2357024" <?=$instance['region'] == '2357024' ? ' selected="selected"' : '';?>>Atlanta</option>
          <option value="2357536" <?=$instance['region'] == '2357536' ? ' selected="selected"' : '';?>>Austin</option>
          <option value="2358820" <?=$instance['region'] == '2358820' ? ' selected="selected"' : '';?>>Baltimore</option>
          <option value="2359991" <?=$instance['region'] == '2359991' ? ' selected="selected"' : '';?>>Baton Rouge</option>
          <option value="2364559" <?=$instance['region'] == '2364559' ? ' selected="selected"' : '';?>>Birmingham</option>
          <option value="2367105" <?=$instance['region'] == '2367105' ? ' selected="selected"' : '';?>>Boston</option>
          <option value="2378426" <?=$instance['region'] == '2378426' ? ' selected="selected"' : '';?>>Charlotte</option>
          <option value="2379574" <?=$instance['region'] == '2379574' ? ' selected="selected"' : '';?>>Chicago</option>
          <option value="2380358" <?=$instance['region'] == '2380358' ? ' selected="selected"' : '';?>>Cincinnati</option>
          <option value="2381475" <?=$instance['region'] == '2381475' ? ' selected="selected"' : '';?>>Cleveland</option>
          <option value="2383489" <?=$instance['region'] == '2383489' ? ' selected="selected"' : '';?>>Colorado Springs</option>
          <option value="2383660" <?=$instance['region'] == '2383660' ? ' selected="selected"' : '';?>>Columbus</option>
          <option value="2388929" <?=$instance['region'] == '2388929' ? ' selected="selected"' : '';?>>Dallas-Ft. Worth</option>
          <option value="2391279" <?=$instance['region'] == '2391279' ? ' selected="selected"' : '';?>>Denver</option>
          <option value="2391585" <?=$instance['region'] == '2391585' ? ' selected="selected"' : '';?>>Detroit</option>
          <option value="2397816" <?=$instance['region'] == '2397816' ? ' selected="selected"' : '';?>>El Paso</option>
          <option value="2407517" <?=$instance['region'] == '2407517' ? ' selected="selected"' : '';?>>Fresno</option>
          <option value="2414469" <?=$instance['region'] == '2414469' ? ' selected="selected"' : '';?>>Greensboro</option>
          <option value="2418046" <?=$instance['region'] == '2418046' ? ' selected="selected"' : '';?>>Harrisburg</option>
          <option value="2423945" <?=$instance['region'] == '2423945' ? ' selected="selected"' : '';?>>Honolulu</option>
          <option value="2424766" <?=$instance['region'] == '2424766' ? ' selected="selected"' : '';?>>Houston</option>
          <option value="2427032" <?=$instance['region'] == '2427032' ? ' selected="selected"' : '';?>>Indianapolis</option>
          <option value="2428184" <?=$instance['region'] == '2428184' ? ' selected="selected"' : '';?>>Jackson</option>
          <option value="2428344" <?=$instance['region'] == '2428344' ? ' selected="selected"' : '';?>>Jacksonville</option>
          <option value="2430683" <?=$instance['region'] == '2430683' ? ' selected="selected"' : '';?>>Kansas City</option>
          <option value="2436704" <?=$instance['region'] == '2436704' ? ' selected="selected"' : '';?>>Las Vegas</option>
          <option value="2441472" <?=$instance['region'] == '2441472' ? ' selected="selected"' : '';?>>Long Beach</option>
          <option value="2442047" <?=$instance['region'] == '2442047' ? ' selected="selected"' : '';?>>Los Angeles</option>
          <option value="2442327" <?=$instance['region'] == '2442327' ? ' selected="selected"' : '';?>>Louisville</option>
          <option value="2449323" <?=$instance['region'] == '2449323' ? ' selected="selected"' : '';?>>Memphis</option>
          <option value="2449808" <?=$instance['region'] == '2449808' ? ' selected="selected"' : '';?>>Mesa</option>
          <option value="2450022" <?=$instance['region'] == '2450022' ? ' selected="selected"' : '';?>>Miami</option>
          <option value="2451822" <?=$instance['region'] == '2451822' ? ' selected="selected"' : '';?>>Milwaukee</option>
          <option value="2452078" <?=$instance['region'] == '2452078' ? ' selected="selected"' : '';?>>Minneapolis</option>
          <option value="2457170" <?=$instance['region'] == '2457170' ? ' selected="selected"' : '';?>>Nashville</option>
          <option value="2458410" <?=$instance['region'] == '2458410' ? ' selected="selected"' : '';?>>New Haven</option>
          <option value="2458833" <?=$instance['region'] == '2458833' ? ' selected="selected"' : '';?>>New Orleans</option>
          <option value="2459115" <?=$instance['region'] == '2459115' ? ' selected="selected"' : '';?>>New York</option>
          <option value="2460389" <?=$instance['region'] == '2460389' ? ' selected="selected"' : '';?>>Norfolk</option>
          <option value="2464592" <?=$instance['region'] == '2464592' ? ' selected="selected"' : '';?>>Oklahoma City</option>
          <option value="2465512" <?=$instance['region'] == '2465512' ? ' selected="selected"' : '';?>>Omaha</option>
          <option value="2466256" <?=$instance['region'] == '2466256' ? ' selected="selected"' : '';?>>Orlando</option>
          <option value="2471217" <?=$instance['region'] == '2471217' ? ' selected="selected"' : '';?>>Philadelphia</option>
          <option value="2471390" <?=$instance['region'] == '2471390' ? ' selected="selected"' : '';?>>Phoenix</option>
          <option value="2473224" <?=$instance['region'] == '2473224' ? ' selected="selected"' : '';?>>Pittsburgh</option>
          <option value="2475687" <?=$instance['region'] == '2475687' ? ' selected="selected"' : '';?>>Portland</option>
          <option value="2477058" <?=$instance['region'] == '2477058' ? ' selected="selected"' : '';?>>Providence</option>
          <option value="2478307" <?=$instance['region'] == '2478307' ? ' selected="selected"' : '';?>>Raleigh</option>
          <option value="2480894" <?=$instance['region'] == '2480894' ? ' selected="selected"' : '';?>>Richmond</option>
          <option value="2486340" <?=$instance['region'] == '2486340' ? ' selected="selected"' : '';?>>Sacramento</option>
          <option value="2487610" <?=$instance['region'] == '2487610' ? ' selected="selected"' : '';?>>Salt Lake City</option>
          <option value="2487796" <?=$instance['region'] == '2487796' ? ' selected="selected"' : '';?>>San Antonio</option>
          <option value="2487889" <?=$instance['region'] == '2487889' ? ' selected="selected"' : '';?>>San Diego</option>
          <option value="2487956" <?=$instance['region'] == '2487956' ? ' selected="selected"' : '';?>>San Francisco</option>
          <option value="2488042" <?=$instance['region'] == '2488042' ? ' selected="selected"' : '';?>>San Jose</option>
          <option value="2490383" <?=$instance['region'] == '2490383' ? ' selected="selected"' : '';?>>Seattle</option>
          <option value="2486982" <?=$instance['region'] == '2486982' ? ' selected="selected"' : '';?>>St. Louis</option>
          <option value="2503713" <?=$instance['region'] == '2503713' ? ' selected="selected"' : '';?>>Tallahassee</option>
          <option value="2503863" <?=$instance['region'] == '2503863' ? ' selected="selected"' : '';?>>Tampa</option>
          <option value="2508428" <?=$instance['region'] == '2508428' ? ' selected="selected"' : '';?>>Tucson</option>
          <option value="2512636" <?=$instance['region'] == '2512636' ? ' selected="selected"' : '';?>>Virginia Beach</option>
          <option value="2514815" <?=$instance['region'] == '2514815' ? ' selected="selected"' : '';?>>Washington</option>
          </optgroup>
          <optgroup label="Venezuela">
          <option value="395273" <?=$instance['region'] == '395273' ? ' selected="selected"' : '';?>>Barcelona</option>
          <option value="468382" <?=$instance['region'] == '468382' ? ' selected="selected"' : '';?>>Barquisimeto</option>
          <option value="395269" <?=$instance['region'] == '395269' ? ' selected="selected"' : '';?>>Caracas</option>
          <option value="395275" <?=$instance['region'] == '395275' ? ' selected="selected"' : '';?>>Ciudad Guayana</option>
          <option value="395270" <?=$instance['region'] == '395270' ? ' selected="selected"' : '';?>>Maracaibo</option>
          <option value="395271" <?=$instance['region'] == '395271' ? ' selected="selected"' : '';?>>Maracay</option>
          <option value="468384" <?=$instance['region'] == '468384' ? ' selected="selected"' : '';?>>Matur&iacute;n</option>
          <option value="395277" <?=$instance['region'] == '395277' ? ' selected="selected"' : '';?>>Turmero</option>
          <option value="395272" <?=$instance['region'] == '395272' ? ' selected="selected"' : '';?>>Valencia</option>
          </optgroup>
          </select>  
          </label>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id( 'expiration' ); ?>"><?php _e( 'Update Trends :' ); ?>
          <select class="widefat" name="<?php echo $this->get_field_name( 'expiration' ); ?>">
            <option value="1" <?=$instance['expiration'] == '1' ? ' selected="selected"' : '';?>>Hourly</option>
            <option value="12" <?=$instance['expiration'] == '12' ? ' selected="selected"' : '';?>>Twice Daily</option>
            <option value="24" <?=$instance['expiration'] == '24' ? ' selected="selected"' : '';?>>Daily</option>
            <option value="168" <?=$instance['expiration'] == '168' ? ' selected="selected"' : '';?>>Weekly</option>
            <option value="720" <?=$instance['expiration'] == '720' ? ' selected="selected"' : '';?>>Monthly</option>
          </select>
          </label>
        </p>

        <p>
          <label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of Trends to Display:' ); ?>
          <select class="widefat" name="<?php echo $this->get_field_name( 'count' ); ?>">
            <option value="1" <?=$instance['count'] == '1' ? ' selected="selected"' : '';?>>One</option>
            <option value="2" <?=$instance['count'] == '2' ? ' selected="selected"' : '';?>>Two</option>
            <option value="3" <?=$instance['count'] == '3' ? ' selected="selected"' : '';?>>Three</option>
            <option value="4" <?=$instance['count'] == '4' ? ' selected="selected"' : '';?>>Four</option>
            <option value="5" <?=$instance['count'] == '5' ? ' selected="selected"' : '';?>>Five</option>
            <option value="6" <?=$instance['count'] == '6' ? ' selected="selected"' : '';?>>Six</option>
            <option value="7" <?=$instance['count'] == '7' ? ' selected="selected"' : '';?>>Seven</option>
            <option value="8" <?=$instance['count'] == '8' ? ' selected="selected"' : '';?>>Eight</option>
            <option value="9" <?=$instance['count'] == '9' ? ' selected="selected"' : '';?>>Nine</option>
            <option value="10" <?=$instance['count'] == '10' ? ' selected="selected"' : '';?>>Ten</option>
          </select>
          </label>
        </p>

	<p>
		<label for="<?php echo $this->get_field_id('consumer_key'); ?>"><?php _e('Consumer Key:'); ?></label>
		<input class="widefat" style="width: 216px;" id="<?php echo $this->get_field_id('consumer_key'); ?>" name="<?php echo $this->get_field_name('consumer_key'); ?>" value="<?php echo $instance['consumer_key']; ?>" />
	</p>

	<p>
		<label for="<?php echo $this->get_field_id('consumer_secret'); ?>"><?php _e('Consumer Secret:'); ?></label>
		<input class="widefat" style="width: 216px;" id="<?php echo $this->get_field_id('consumer_secret'); ?>" name="<?php echo $this->get_field_name('consumer_secret'); ?>" value="<?php echo $instance['consumer_secret']; ?>" />
	</p>

	<p>
		<label for="<?php echo $this->get_field_id('access_token'); ?>"><?php _e('Access Token:'); ?></label>
		<input class="widefat" style="width: 216px;" id="<?php echo $this->get_field_id('access_token'); ?>" name="<?php echo $this->get_field_name('access_token'); ?>" value="<?php echo $instance['access_token']; ?>" />
	</p>

	<p>
		<label for="<?php echo $this->get_field_id('access_token_secret'); ?>"><?php _e('Access Token Secret:'); ?></label>
		<input class="widefat" style="width: 216px;" id="<?php echo $this->get_field_id('access_token_secret'); ?>" name="<?php echo $this->get_field_name('access_token_secret'); ?>" value="<?php echo $instance['access_token_secret']; ?>" />
	</p>

	<?php
	
	}
}
?>