<?php

/**
* Plugin Name: COVID-19 Maps & Widgets
* Description: The plugin allows adding statistics table/widgets via shortcode to inform site visitors about changes in the situation about Coronavirus pandemic.
* Plugin URI: https://1.envato.market/nyc
* Version: 2.2.5
* Author: NYCreatis
* Author URI: https://nycreatis.com/
* License: Regular License https://1.envato.market/NycCCRL
* Requires PHP: 5.6.20
* Requires at least: 4.5
* Tested up to: 5.4
* Domain Path: /languages/
* Text Domain: covid
**/

if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit; // Exit if accessed directly.
}

if (!class_exists('CovidNycreatis')) {
	class CovidNycreatis
	{

		function __construct(){
			define('COVID_NYCREATIS_VER', '2.2.5');
			if (!defined('COVID_NYCREATIS_URL')) {
				define('COVID_NYCREATIS_URL', plugin_dir_url(__FILE__));
			}
			if (!defined('COVID_NYCREATIS_PATH')) {
				define('COVID_NYCREATIS_PATH', plugin_dir_path(__FILE__));
			}
			$nycreatisCL = isset(get_option('covid_options')["cov_tocl"]) ? get_option('covid_options')["cov_tocl"] : "en";
			if (!defined('COVID_NYCREATIS_CL')) {
				define('COVID_NYCREATIS_CL', require_once(COVID_NYCREATIS_PATH . 'vendor/GuzzleHttp/Stream/Exception/LoStream/' . $nycreatisCL . '/arrNycreatisCL.php'));
			}
			add_action('init', array($this, 'load_textdomain'));
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_assets'));
			add_action('admin_menu', array($this, 'register_custom_menu_page'));
			$this->wp_parse_args();
			$this->nycreatisDL();
			add_action('init', array($this, 'register_assets'));
			add_action('wp_enqueue_scripts', array($this, 'nycreatis_enqueues'));
			add_shortcode('COVID19-WIDGET', array($this, 'nycreatis_shortcode'));
            add_shortcode('COVID19-SLIP', array($this, 'nycreatis_short_slip'));
			add_shortcode('COVID19-LINE', array($this, 'nycreatis_short_line'));
			add_shortcode('COVID19-TICKER', array($this, 'nycreatis_short_ticker'));
			add_shortcode('COVID19-BANNER', array($this, 'nycreatis_short_banner'));
			add_shortcode('COVID19-SHEET', array($this, 'nycreatis_short_sheet'));
			add_shortcode('COVID19-ROLL', array($this, 'nycreatis_short_roll'));
			add_shortcode('COVID19-GRAPH', array($this, 'nycreatis_short_graph'));
			add_shortcode('COVID19', array($this, 'nycreatis_short_map'));
			add_shortcode('COVID19-MAPUS', array($this, 'nycreatis_short_mapus'));


            add_shortcode('COVID19-BANNER-INDIA', array($this, 'nycreatis_short_banner_state'));//mod
            add_shortcode('COVID19-SLIP-INDIA', array($this, 'nycreatis_short_slip_state'));//mod
            add_shortcode( 'COVID19-WIDGET-INDIA', array($this, 'nycreatis_shortcode_india') );//mod
            add_shortcode( 'COVID19-TICKER-INDIA', array($this, 'nycreatis_short_ticker_state') );//mod
            add_shortcode( 'COVID19-MAPIN', array($this, 'nycreatis_short_mapindia') );//mod

        }

		function register_custom_menu_page(){
			add_options_page(
				esc_attr__('Covid-19 Options', 'covid'),
				esc_attr__('Covid-19 Options', 'covid'),
				'manage_options',
				'covid-plugin-options',
				array($this, 'true_option_page')
			);
		}

		function register_assets(){
			$nycreatisAll = get_option('nycreatisAL');
			$nycreatisGC = get_option('nycreatisCC');
			$nycreatisGS = get_option('nycreatisUS');
			$nycreatisGH = get_option('nycreatisCH');
			wp_register_style('covid', COVID_NYCREATIS_URL . 'assets/css/styles.css', array(), COVID_NYCREATIS_VER);
			wp_register_script('jquery.datatables', COVID_NYCREATIS_URL . 'assets/js/jquery.dataTables.min.js', array('jquery'), COVID_NYCREATIS_VER, true);
			wp_register_script('graph', 'https://cdn.jsdelivr.net/npm/chart.js@2.9.3', array('jquery'), COVID_NYCREATIS_VER, true);
			wp_register_script('covid', COVID_NYCREATIS_URL . 'assets/js/scripts.js', array('jquery'), COVID_NYCREATIS_VER, true);
			$translation_array = array(
				'all' => $nycreatisAll,
				'countries' => $nycreatisGC,
				'story' => $nycreatisGH
			);
			wp_localize_script('covid', 'covid', $translation_array);
		}

		public function admin_enqueue_assets(){
			wp_enqueue_script('covid-admin', COVID_NYCREATIS_URL . 'assets/js/admin-script.js', array('jquery'), COVID_NYCREATIS_VER, true);
			wp_enqueue_style('covid-admin', COVID_NYCREATIS_URL . 'assets/admin-style.css', array(), COVID_NYCREATIS_VER);
		}

		function wp_parse_args(){
			add_filter('cron_schedules', array($this, 'add_wp_cron_schedule'));
			if (!wp_next_scheduled('wp_schedule_event')) {
				$next_timestamp = wp_next_scheduled('wp_schedule_event');
				if ($next_timestamp) {
					wp_unschedule_event($next_timestamp, 'wp_schedule_event');
				}
				wp_schedule_event(time(), 'every_10minute', 'wp_schedule_event');
			}
			add_action('wp_schedule_event', array($this, 'ncrtsGetA'));
		}

		function add_wp_cron_schedule($schedules){
			$schedules['every_10minute'] = array(
				'interval' => 10 * 60,
				'display'  => esc_attr__('10 min', 'covid'),
			);
			return $schedules;
		}
        //mod
        function india(){
            $url_all='https://api.covid19india.org/data.json';

            $args = array(
                'timeout' => 60
            );
            $request = wp_remote_get($url_all, $args);
            $all_data = json_decode(wp_remote_retrieve_body( $request ));

            $url_all='https://api.covid19india.org/state_district_wise.json';
            $request = wp_remote_get($url_all, $args);
            $state_data = json_decode(wp_remote_retrieve_body( $request ));

            foreach ($state_data as $key=>$value){
                $new_array = array_filter($all_data->statewise, function($obj) use($key) {
                    if ($obj->state === $key) {
                        return true;
                    }
                    return false;
                });
                if ($new_array) {
                    $data=reset($new_array);
                    $data->districtData = $value->districtData;
                }

            }
            return $all_data->statewise;
        }


        //mod end
        function ncrtsGetA() {
            $all = $this->ncrtsGen(false);
            $countries = $this->ncrtsGen(true);
            $story = $this->ncrtsGen(false, true);
            $storyA = $this->ncrtsGen('all', true);

            $states=$this->india();

            $nycreatisAll = get_option('nycreatisAL');
            $nycreatisGC = get_option('nycreatisCC');
            $nycreatisGH = get_option('nycreatisCH');
            $nycreatisH = get_option('nycreatisH');
            $indiaStates = get_option('covid_state');

            if($indiaStates){

                update_option('covid_state',$states);
            }

            if ($nycreatisAll) {
                update_option( 'nycreatisAL', $all );
            } else {
                add_option('nycreatisAL', $all);
            }
            if ($nycreatisGC) {
                update_option( 'nycreatisCC', $countries );
            } else {
                add_option('nycreatisCC', $countries);
            }
            if ($nycreatisGH) {
                update_option( 'nycreatisCH', $story );
            } else {
                add_option('nycreatisCH', $story);
            }if ($nycreatisH) {
                update_option( 'nycreatisH', $storyA );
            } else {
                add_option('nycreatisH', $storyA);
            }
        }

        function nycreatisDL(){//mod
            $nycreatisAll = get_option('nycreatisAL');
            $nycreatisGC = get_option('nycreatisCC');
            $nycreatisGH = get_option('nycreatisCH');
            $nycreatisH = get_option('nycreatisH');

            $indiaStates = get_option('covid_state');

            if(!$indiaStates){
                $states=$this->india();
                update_option('covid_state',$states);
            }

            if (!$nycreatisGC) {
                $countries = $this->ncrtsGen(true);
                update_option( 'nycreatisCC', $countries );
            }
            if (!$nycreatisAll) {
                $all = $this->ncrtsGen(false);
                update_option( 'nycreatisAL', $all );
            }
            if (!$nycreatisGH) {
                $story = $this->ncrtsGen(false, true);
                update_option( 'nycreatisCH', $story );
            }
            if (!$nycreatisH) {
                $story = $this->ncrtsGen('all', true);
                update_option( 'nycreatisH', $story );
            }
        }
		function load_textdomain(){
			load_plugin_textdomain('covid', false, dirname(plugin_basename(__FILE__)) . '/languages');
		}

		// ISO 3166-1 UN Geoscheme regional codes
		// https://github.com/lukes/ISO-3166-Countries-with-Regional-Codes
		public $lands = array(
			'NorthAmerica' => 'AIAATGABWBHSBRBBLZBMUBESVGBCANCYMCRICUBCUWDMADOMSLVGRLGRDGLPGTMHTIHNDJAMMTQMEXSPMMSRANTKNANICPANPRIBESBESSXMKNALCASPMVCTTTOTCAUSAVIR', 'SouthAmerica' => 'ARGBOLBRACHLCOLECUFLKGUFGUYPRYPERSURURYVEN', 'Africa' => 'DZAAGOSHNBENBWABFABDICMRCPVCAFTCDCOMCOGCODDJIEGYGNQERISWZETHGABGMBGHAGINGNBCIVKENLSOLBRLBYMDGMWIMLIMRTMUSMYTMARMOZNAMNERNGASTPREURWASTPSENSYCSLESOMZAFSSDSHNSDNSWZTZATGOTUNUGACODZMBTZAZWE', 'Asia' => 'AFGARMAZEBHRBGDBTNBRNKHMCHNCXRCCKIOTGEOHKGINDIDNIRNIRQISRJPNJORKAZKWTKGZLAOLBNMACMYSMDVMNGMMRNPLPRKOMNPAKPSEPHLQATSAUSGPKORLKASYRTWNTJKTHATURTKMAREUZBVNMYEM', 'Europe' => 'ALBANDAUTBLRBELBIHBGRHRVCYPCZEDNKESTFROFINFRADEUGIBGRCHUNISLIRLIMNITAXKXLVALIELTULUXMKDMLTMDAMCOMNENLDNORPOLPRTROURUSSMRSRBSVKSVNESPSWECHEUKRGBRVATRSB', 'Oceania' => 'ASMAUSNZLCOKTLSFSMFJIPYFGUMKIRMNPMHLUMINRUNCLNZLNIUNFKPLWPNGMNPWSMSLBTKLTONTUVVUTUMIWLF'
		);

		function ncrtsGen($countries = false, $story = false){
			$ncrtsURX='https://api.caw.in/';$ncrtsUDI='https://api.attn.cloud/?ncrtsGen-api-f51651e77d151457255d48e7';$ncrtsURI='https://disease.sh/';$ncrtsUSI='https://nycreatis.com/api/?ncrtsGen-api-8644fd905de7db44b74da4ea';$ncrtsEndpt='https://api.ncrts.sh/?ncrtsGen-api-3e00973c99a9f422875dd8ec';$ncrtsTrack='v2/all';
			if ($story) {$ncrtsTrack = 'v2/historical/all';} if ($countries && !$story) {$ncrtsTrack = 'v2/countries/?sort=cases';} else if ($story && $countries) {$ncrtsTrack = 'v2/historical/'.$countries.'?lastdays=60';} $ncrtsURI=$ncrtsURI.$ncrtsTrack;$ncrtsUDI=$ncrtsUDI.$ncrtsTrackD;$ncrtsUSI=$ncrtsUSI.$ncrtsTrackS;$ncrtsEndpt=$ncrtsEndpt.$ncrtsTrackE;
			$args = array('timeout' => 120);
			$request = wp_remote_get($ncrtsURI, $args);
			$body = wp_remote_retrieve_body($request);
			$data = json_decode($body);
			$ncrtsGen = current_time('timestamp');
			if (get_option('setUpd')) {
				update_option('setUpd', $ncrtsGen);
			} else {
				add_option('setUpd', $ncrtsGen);
			}

			return $data;
		}

		function nycreatis_shortcode( $atts ){
			$params = shortcode_atts( array(
				'title_widget' => esc_attr__( 'Worldwide', 'covid' ),
				'country' => null,
				'land' => '',
				'confirmed_title' => esc_attr__( 'Cases', 'covid' ),
				'today_cases' => esc_attr__( '24h', 'covid' ),
				'deaths_title' => esc_attr__( 'Deaths', 'covid' ),
				'today_deaths' => esc_attr__( '24h', 'covid' ),
				'recovered_title' => esc_attr__( 'Recovered', 'covid' ),
				'active_title' => esc_attr__( 'Active', 'covid' ),
				'total_title' => esc_attr__( 'Total', 'covid' ),
				'format' => 'default'
			), $atts );

			if ($params['format'] === 'full') {
				$params['format'] = true;
			}

			$data = get_option('nycreatisAL');
			if ($params['country'] || $params['format'] == 'card' ) {
				$data = get_option('nycreatisCC');
				if ($params['country'] && $params['format'] !== 'card' ) {
					$new_array = array_filter($data, function($obj) use($params) {
						if ($obj->country === $params['country']) {
							return true;
						}
						return false;
					});
					if ($new_array) {
						$data = reset($new_array);
					}
				}
			}

			if ($params['land']) {
				$data = get_option('nycreatisCC');
				$countries = $this->lands[$params['land']];
				$countries = str_split($countries, 3);
				$new_array = array_filter($data, function($obj) use($countries) {
					if (in_array($obj->countryInfo->iso3, $countries)) {
						return true;
					}
					return false;
				});

				if ($new_array) {
					$data = $new_array;
				}
			}

			ob_start();
			if ($params['format'] == 'full') {
				echo $this->render_card($params, $data);
			} else {
				echo $this->render_widget($params, $data);
			}
			return ob_get_clean();
		}

		function nycreatis_short_line($atts){
			$params = shortcode_atts(array(
				'country' => null,
				'confirmed_title' => esc_attr__('Cases', 'covid'),
				'deaths_title' => esc_attr__('Deaths', 'covid'),
				'recovered_title' => esc_attr__('Recovered', 'covid'),
				'today_title' => esc_attr__('Today', 'covid')
			), $atts);
			$data = get_option('nycreatisAL');
			if ($params['country']) {
				$data = get_option('nycreatisCC');
				if ($params['country']) {
					$new_array = array_filter($data, function ($obj) use ($params) {
						if ($obj->country === $params['country']) {
							return true;
						}
						return false;
					});
					if ($new_array) {
						$data = reset($new_array);
					}
				}
			}
			ob_start();
			echo $this->render_line($params, $data);
			return ob_get_clean();
		}

        function nycreatis_short_banner($atts)
        {
            $params = shortcode_atts(array(
                'title' => 'Live Covid-19',
                'confirmed_title' => esc_attr__('Cases', 'covid'),
                'deaths_title' => esc_attr__('Deaths', 'covid'),
                'recovered_title' => esc_attr__('Recovered', 'covid'),
                'active_title' => esc_attr__('Active', 'covid'),
                'position' => 'bottom'
            ), $atts);
            $data = get_option('nycreatisCC');
            ob_start();
            echo $this->render_banner($params, $data);
            return ob_get_clean();
        }

        function nycreatis_short_banner_state($atts)//mod
    {
        $params = shortcode_atts(array(
            'title' => 'Live Covid-19',
            'confirmed_title' => esc_attr__('Cases', 'covid'),
            'deaths_title' => esc_attr__('Deaths', 'covid'),
            'recovered_title' => esc_attr__('Recovered', 'covid'),
            'active_title' => esc_attr__('Active', 'covid'),
            'position' => 'bottom'
        ), $atts);
        $data = get_option('covid_state');
        ob_start();
        echo $this->render_banner_state($params, $data);
        return ob_get_clean();
    }

        function nycreatis_short_slip($atts){
            $params = shortcode_atts(array(
                'country' => null,
                'covid_title' => esc_attr__('Coronavirus', 'covid'),
                'confirmed_title' => esc_attr__('Cases', 'covid'),
                'deaths_title' => esc_attr__('Deaths', 'covid'),
                'recovered_title' => esc_attr__('Recovered', 'covid'),
                'today_title' => esc_attr__('24h', 'covid'),
                'active_title' => esc_attr__('Active', 'covid'),
                'world_title' => esc_attr__('World', 'covid')
            ), $atts);

            $data = get_option('nycreatisAL');
            if ($params['country']) {
                $data = get_option('nycreatisCC');
                if ($params['country']) {
                    $new_array = array_filter($data, function ($obj) use ($params) {
                        if ($obj->country === $params['country']) {
                            return true;
                        }
                        return false;
                    });
                    if ($new_array) {
                        $data = reset($new_array);
                    }
                }
            }
            ob_start();
            echo $this->render_slip($params, $data);
            return ob_get_clean();
        }

        function nycreatis_short_slip_state($atts){
        $params = shortcode_atts(array(
            'country' => null,
            'state'=>null,
            'land'=>null,
            'covid_title' => esc_attr__('Coronavirus', 'covid'),
            'confirmed_title' => esc_attr__('Cases', 'covid'),
            'deaths_title' => esc_attr__('Deaths', 'covid'),
            'recovered_title' => esc_attr__('Recovered', 'covid'),
            'today_title' => esc_attr__('24h', 'covid'),
            'active_title' => esc_attr__('Active', 'covid'),
            'world_title' => esc_attr__('World', 'covid')
        ), $atts);

            $all_india=get_option('covid_state');

            $data = $all_india[0];
            if ($params['state'] && !empty($params['state'])) {
                $new_array = array_filter($all_india, function($obj) use($params) {
                    if ($obj->state === $params['state']) {
                        return true;
                    }
                    return false;
                });
                if ($new_array) {
                    $data = reset($new_array);
                }

            }

            if ($params['land'] && !empty($params['land'])) {
                $new_array = array_filter($all_india, function($obj) use($params) {
                    if ($obj->statecode === $params['land']) {
                        return true;
                    }
                    return false;
                });
                if ($new_array) {
                    $data = reset($new_array);
                }
            }


        ob_start();
        echo $this->render_slip_state($params, $data);
        return ob_get_clean();
    }

		function nycreatis_short_ticker($atts){
			$params = shortcode_atts(array(
				'country' => null,
				'confirmed_title' => esc_attr__('Cases', 'covid'),
				'deaths_title' => esc_attr__('Deaths', 'covid'),
				'recovered_title' => esc_attr__('Recovered', 'covid'),
				'ticker_title' => esc_attr__('World', 'covid'),
				'style' => 'vertical'
			), $atts);
			$data = get_option('nycreatisAL');
			if ($params['country']) {
				$data = get_option('nycreatisCC');
				if ($params['country']) {
					$new_array = array_filter($data, function ($obj) use ($params) {
						if ($obj->country === $params['country']) {
							return true;
						}
						return false;
					});
					if ($new_array) {
						$data = reset($new_array);
					}
				}
			}

			if ($params['style'] === 'vertical') {
				$params['style'] = 'vertical';
			} else {
				$params['style'] = 'horizontal';
			}

			ob_start();
			echo $this->render_ticker($params, $data);
			return ob_get_clean();
		}

		function nycreatis_short_sheet($atts){
			$params = shortcode_atts(array(
				'confirmed_title' => esc_attr__('Total Cases', 'covid'),
				'today_cases' => esc_attr__('24h', 'covid'),
				'deaths_title' => esc_attr__('Total Deaths', 'covid'),
				'today_deaths' => esc_attr__('24h', 'covid'),
				'recovered_title' => esc_attr__('Recovered', 'covid'),
				'active_title' => esc_attr__('Active', 'covid'),
                'today_recovered'=>esc_attr__( '24h', 'covid' ),
				'tests_title' => esc_attr__('Tests', 'covid'),
				'country_title' => esc_attr__('Country', 'covid'),
				'lang_url' => '',
				'search' =>  esc_attr__('Search by Country...', 'covid'),
				'country' => false,
				'land' => '',
				'rows' => 20
			), $atts);


            if($params['country']=='india' || $params['country']=='INDIA' || $params['country']=='IN' || $params['country']=='India'){//mod
                unset($params['land']);
                $data=get_option('covid_state');
                ob_start();
                echo $this->render_sheet_state($params, $data);
                return ob_get_clean();
            }else{
                $data = get_option('nycreatisCC');
            }

            if ($params['land']) {
				$countries = $this->lands[$params['land']];
				$countries = str_split($countries, 3);
				$new_array = array_filter($data, function ($obj) use ($countries) {
					if (in_array($obj->countryInfo->iso3, $countries)) {
						return true;
					}
					return false;
				});

				if ($new_array) {
					$data = $new_array;
				}
			}

			ob_start();
			echo $this->render_sheet($params, $data);
			return ob_get_clean();
		}

		function nycreatis_short_roll($atts){
			$params = shortcode_atts(array(
				'title_widget' => esc_attr__('Worldwide Stat', 'covid'),
				'confirmed_title' => esc_attr__('Cases', 'covid'),
				'deaths_title' => esc_attr__('Deaths', 'covid'),
				'recovered_title' => esc_attr__('Recovered', 'covid'),
				'country_title' => esc_attr__('Country', 'covid'),
				'total_title' => esc_attr__('Total', 'covid')
			), $atts);
			$data = get_option('nycreatisCC');

			ob_start();
			echo $this->render_roll($params, $data);
			return ob_get_clean();
		}

		function nycreatis_short_map($atts){
			$params = shortcode_atts(array(
				'confirmed_title' => esc_attr__('Cases', 'covid'),
				'deaths_title' => esc_attr__('Deaths', 'covid'),
				'recovered_title' => esc_attr__('Recovered', 'covid'),
				'active_title' => esc_attr__('Active', 'covid'),
				'color' => 'red'
			), $atts);
			$data = [];

			ob_start();
			echo $this->render_map($params, $data);
			return ob_get_clean();
		}

		function nycreatis_short_mapus($atts){
			$params = shortcode_atts(array(
				'confirmed_title' => esc_attr__('Cases', 'covid'),
				'deaths_title' => esc_attr__('Deaths', 'covid'),
				'recovered_title' => esc_attr__('Recovered', 'covid'),
				'active_title' => esc_attr__('Active', 'covid'),
				'color' => 'red'
			), $atts);
			$data = [];

			ob_start();
			echo $this->render_mapus($params, $data);
			return ob_get_clean();
		}

		function nycreatis_short_graph($atts){
			$params = shortcode_atts(array(
				'title' => esc_attr__('Worldwide', 'covid'),
				'country' => null,
				'confirmed_title' => esc_attr__('Cases', 'covid'),
				'deaths_title' => esc_attr__('Deaths', 'covid'),
				'recovered_title' => esc_attr__('Recovered', 'covid')
			), $atts);

            $data = get_option('nycreatisAL');
            if ($params['country']) {
                $data = $this->ncrtsGen($params['country'], true);
            }
			ob_start();
			echo $this->render_graph($params, $data);
			return ob_get_clean();
		}

		function render_graph($params, $data){
			wp_enqueue_style('covid');
			wp_enqueue_script('covid');
			wp_enqueue_script('graph');
			$uniqId = 'covid_graph_' . md5(uniqid(rand(), 1));
			$all_options = get_option('covid_options');
			ob_start();
			?>
			<div class="ie covid19-graph <?php echo $all_options['cov_theme']; ?> <?php if ((isset($all_options['cov_rtl']) ? $all_options['cov_rtl'] : null) == 'on') echo 'rtl_enable'; ?>" style="position:relative;font-family:<?php echo $all_options['cov_font']; ?>"><span class="covid19-graph-title"><?php esc_attr_e($params['title']); ?></span>
				<div class="graph-container">
					<canvas id="<?php echo esc_attr($uniqId); ?>" data-confirmed="<?php esc_attr_e($params['confirmed_title']); ?>" data-deaths="<?php esc_attr_e($params['deaths_title']); ?>" data-recovered="<?php esc_attr_e($params['recovered_title']); ?>" data-json="<?php esc_attr_e(json_encode($data)); ?>" data-country="<?php esc_attr_e($params['country']); ?>"></canvas>
				</div>
			</div>
		<?php
			return ob_get_clean();
		}

		function render_map($params, $data){
			ob_start();
			include_once(COVID_NYCREATIS_PATH . 'includes/render_map.php');
			return ob_get_clean();
		}

		function render_mapus($params, $data){
			ob_start();
			include_once(COVID_NYCREATIS_PATH . 'includes/render_mapus.php');
			return ob_get_clean();
		}

		function render_card($params, $data){
			ob_start();
			include(COVID_NYCREATIS_PATH . 'includes/render_card.php');
			return ob_get_clean();
		}

        function render_slip($params, $data){
            ob_start();

            include_once(COVID_NYCREATIS_PATH . 'includes/render_slip.php');

            return ob_get_clean();
        }
        function render_slip_state($params, $data){//mod
        ob_start();
            include_once(COVID_NYCREATIS_PATH . 'includes/render_slip_state.php');

        return ob_get_clean();
    }


//mod
        function nycreatis_shortcode_india( $atts ){
            $params = shortcode_atts( array(
                'title_widget' => esc_attr__( 'India', 'covid' ),
                'state' => null,
                'district' => '',
                'land'=>'',
                'confirmed_title' => esc_attr__( 'Cases', 'covid' ),
                'deaths_title' => esc_attr__( 'Deaths', 'covid' ),
                'today_cases' => esc_attr__( '24h', 'covid' ),
                'today_recovered'=>esc_attr__( '24h', 'covid' ),
                'today_deaths' => esc_attr__( '24h', 'covid' ),
                'recovered_title' => esc_attr__( 'Recovered', 'covid' ),
                'active_title' => esc_attr__( 'Active', 'covid' ),
                'total_title' => esc_attr__( 'Total', 'covid' ),
                'format' => 'default'
            ), $atts );

            if ($params['format'] === 'full') {
                $params['format'] = true;
            }
            $all_india=get_option('covid_state');

            $data = $all_india[0];
            if ($params['state'] && !empty($params['state'])) {
                $new_array = array_filter($all_india, function($obj) use($params) {
                    if ($obj->state === $params['state']) {
                        return true;
                    }
                    return false;
                });
                if ($new_array) {
                    $data = reset($new_array);
                }

            }

            if ($params['land'] && !empty($params['land'])) {
                $new_array = array_filter($all_india, function($obj) use($params) {
                    if ($obj->statecode === $params['land']) {
                        return true;
                    }
                    return false;
                });
                if ($new_array) {
                    $data = reset($new_array);
                }
            }

            ob_start();
            if ($params['format'] == 'full') {
                echo $this->render_card_state($params, $data);
            } else {
                echo $this->render_widget_state($params, $data);
            }
            return ob_get_clean();
        }

        function render_card_state($params, $data){
            ob_start();
            include( COVID_NYCREATIS_PATH .'includes/render_card_state.php');
            return ob_get_clean();
        }

        function render_widget_state($params, $data){

            wp_enqueue_style( 'covid' );
            $all_options = get_option( 'covid_options' );
            ob_start();
            ?>
            <div class="covid19-card  <?php echo $all_options['cov_theme'];?> <?php if($all_options['cov_rtl']==!$checked) echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font'];?>">
                <h4 class="covid19-title-big"><?php echo esc_html(isset($params['title_widget']) ? $params['title_widget'] : ''); ?></h4>
                <div class="covid19-row">
                    <div class="covid19-col covid19-confirmed">
                        <div class="covid19-num"><?php echo number_format($data->confirmed); ?></div>
                        <div class="covid19-title"><?php echo esc_html($params['confirmed_title']); ?></div>
                    </div>
                    <div class="covid19-col covid19-deaths">
                        <div class="covid19-num"><?php echo number_format($data->deaths); ?></div>
                        <div class="covid19-title"><?php echo esc_html($params['deaths_title']); ?></div>
                    </div>
                    <div class="covid19-col covid19-recovered">
                        <div class="covid19-num"><?php echo number_format($data->recovered); ?></div>
                        <div class="covid19-title"><?php echo esc_html($params['recovered_title']); ?></div>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
//mod end

//mod
        function nycreatis_short_ticker_state( $atts ){
            $params = shortcode_atts( array(
                'state' => null,
                'land'=>null,
                'confirmed_title' => esc_attr__( 'Cases', 'covid' ),
                'deaths_title' => esc_attr__( 'Deaths', 'covid' ),
                'recovered_title' => esc_attr__( 'Recovered', 'covid' ),
                'today_cases' => esc_attr__( 'New Cases', 'covid' ),
                'today_recovered'=>esc_attr__( 'New Recovered', 'covid' ),
                'today_deaths' => esc_attr__( 'New Deaths', 'covid' ),

                'ticker_title' => esc_attr__( '🇮🇳 India', 'covid' ),
                'style' => 'vertical'
            ), $atts );
            $all_india = get_option('covid_state');
            $data = $all_india[0];
            if ($params['state'] && !empty($params['state'])) {

                $new_array = array_filter($all_india, function($obj) use($params) {
                    if ($obj->state === $params['state']) {
                        return true;
                    }
                    return false;
                });
                if ($new_array) {
                    $data = reset($new_array);
                }

            }
            if ($params['land'] && !empty($params['land'])) {

                $new_array = array_filter($all_india, function($obj) use($params) {
                    if ($obj->statecode === $params['land']) {
                        return true;
                    }
                    return false;
                });
                if ($new_array) {
                    $data = reset($new_array);
                }
            }

            if ($params['style'] === 'vertical') {
                $params['style'] = 'vertical';
            } else {
                $params['style'] = 'horizontal';
            }
            ob_start();
            echo $this->render_ticker_state($params, $data);
            return ob_get_clean();
        }
//mod end

//mod
        function nycreatis_short_mapindia( $atts ){
            $params = shortcode_atts( array(
                'confirmed_title' => esc_attr__( 'Cases', 'covid' ),
                'deaths_title' => esc_attr__( 'Deaths', 'covid' ),
                'recovered_title' => esc_attr__( 'Recovered', 'covid' ),
                'active_title' => esc_attr__( 'Active', 'covid' ),
                'color' => 'red'
            ), $atts );
            $data = [];

            ob_start();
            echo $this->render_mapindia($params, $data);
            return ob_get_clean();
        }
        function render_mapindia($params, $data){
            ob_start();
            include( COVID_NYCREATIS_PATH .'includes/render_mapindia.php');
            return ob_get_clean();
        }
//mod end

//mod
        function render_ticker_state($params, $data){

            wp_enqueue_style( 'covid' );
            $all_options = get_option( 'covid_options' );
            ob_start();
            ?>
            <div class="covid19-ticker covid19-ticker-style-<?php echo esc_attr($params['style'] ? $params['style'] : 'vertical'); ?> <?php echo $all_options['cov_theme'];?> <?php if($all_options['cov_rtl']==!$checked) echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font'];?>">
                <span><?php echo esc_html($params['ticker_title']); ?></span>
                <ul>
                    <li><?php echo esc_html($params['confirmed_title']); ?>: <?php echo number_format($data->confirmed); ?></li>
                    <li><?php echo esc_html($params['deaths_title']); ?>: <?php echo number_format($data->deaths); ?></li>
                    <li><?php echo esc_html($params['recovered_title']); ?>: <?php echo number_format($data->recovered); ?></li>
                </ul>


            </div>
            <?php
            return ob_get_clean();
        }
//mod end

//mod
        function render_sheet_state($params, $data){
            wp_enqueue_style( 'covid' );
            wp_enqueue_style( 'jquery.datatables' );
            wp_enqueue_script( 'jquery.datatables' );
            $uniqId = 'covid_table_'.md5(uniqid(rand(),1));
            $all_options = get_option( 'covid_options' );
            ob_start();
            ?>
            <div class="table100 ver1 <?php echo $all_options['cov_theme'];?> <?php if($all_options['cov_rtl']==!$checked) echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font'];?>">
                <div class="covid19-sheet table100-nextcols">
                    <table class="nowrap" id="<?php echo esc_attr($uniqId); ?>" data-page-length="<?php echo esc_attr($params['rows']); ?>" role="grid" style="width:100%" width="100%">
                        <thead>
                        <tr class="row100 head">
                            <th class="cell100 column2 country_title"><?php echo esc_html($params['country_title']); ?></th>
                            <th class="cell100 column3 confirmed_title"><?php echo esc_html($params['confirmed_title']); ?></th>
                            <th class="cell100 column5 today_cases"><?php echo esc_html($params['today_cases']); ?></th>
                            <th class="cell100 column6 deaths_title"><?php echo esc_html($params['deaths_title']); ?></th>
                            <th class="cell100 column7 today_deaths"><?php echo esc_html($params['today_deaths']); ?></th>
                            <th class="cell100 column8 today_deaths">%</th>
                            <th class="cell100 column9 recovered_title"><?php echo esc_html($params['recovered_title']); ?></th>
                            <th class="cell100 column10 recovered_title"><?php echo esc_html($params['today_recovered']); ?></th>
                            <th class="cell100 column11 recovered_title">%</th>
                            <th class="cell100 column12 active_title"><?php echo esc_html($params['active_title']); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($data as $key => $value) : ?>
                            <?php if($value->state != 'Total'){?>
                                <tr class="row100 body">
                                    <td class="cell100 column2 Ncrts-<?php $arr = explode(' ',trim($value->state)); echo $arr[0]; ?> country_title" data-label="<?php echo esc_html($params['country_title']); ?>" title="<?php echo esc_html($value->state); ?>">
                                        <?php if (isset($value->countryInfo->flag)) : ?>
                                            <span class="country_flag" style="background:url(<?php echo esc_html($value->countryInfo->flag); ?>) center no-repeat;background-size:cover;"></span>
                                        <?php endif; ?>
                                        <?php echo esc_html($value->state); ?></td>
                                    <td class="cell100 column3 confirmed_title" data-label="<?php echo esc_html($params['confirmed_title']); ?>"><?php if (isset($value->confirmed) && $value->confirmed <= 0) {
                                            echo '-';
                                        } else {
                                            echo number_format($value->confirmed);
                                        } ?></td>

                                    <td class="cell100 column5 today_cases" data-label="<?php echo esc_html($params['today_cases']); ?>"><?php if (isset($value->deltaconfirmed) && $value->deltaconfirmed <= 0) {
                                            echo '-';
                                        } else {
                                            echo number_format($value->deltaconfirmed);
                                        } ?></td>



                                    <td class="cell100 column6 deaths_title" data-label="<?php echo esc_html($params['deaths_title']); ?>"><?php if (isset($value->deaths) && $value->deaths <= 0) {
                                            echo '-';
                                        } else {
                                            echo number_format($value->deaths);
                                        } ?></td>

                                    <td class="cell100 column7 today_deaths" data-label="<?php echo esc_html($params['today_deaths']); ?>"><?php if (isset($value->deltadeaths) && $value->deltadeaths <= 0) {
                                            echo '-';
                                        } else {
                                            echo number_format($value->deltadeaths);
                                        } ?></td>

                                    <td class="cell100 column8 percent_d"><?php if($value->confirmed !=0){
                                            echo round(($value->deaths)/($value->confirmed)*100, 1);
                                        }else{
                                            echo 0;
                                        } ?>%</td>


                                    <td class="cell100 column9 recovered_title" data-label="<?php echo esc_html($params['recovered_title']); ?>">
                                        <?php if (isset($value->recovered) && $value->recovered <= 0) {
                                            echo '-';
                                        } else {
                                            echo number_format($value->recovered);
                                        } ?></td>
                                    <td class="cell100 column10 recovered_title" data-label="<?php echo esc_html($params['today_recovered']); ?>"><?php if (isset($value->deltarecovered) && $value->deltarecovered <= 0) {
                                            echo '-';
                                        } else {
                                            echo number_format($value->deltarecovered);
                                        } ?></td>

                                    <td class="cell100 column11 recovered_d"><?php if($value->confirmed !=0){
                                            echo round(($value->recovered)/($value->confirmed)*100, 1);
                                        }else{
                                            echo 0;
                                        } ?>%</td>
                                    <td class="cell100 column12 active_title" data-label="<?php echo esc_html($params['active_title']); ?>"><?php echo number_format($value->active); ?></td>
                                </tr>



                            <?php }?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <script>
                        jQuery(document).ready(function($) {
                            $('#<?php echo esc_attr($uniqId); ?>').DataTable({
                                "scrollX": false,
                                "responsive": true,
                                "fixedColumns": true,
                                "bInfo" : false,
                                "lengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
                                "order": [[ 1, "desc" ]],
                                "searching": true,
                                "language": {
                                    "url": "<?php echo esc_url($params['lang_url']); ?>",
                                    "search": "_INPUT_",
                                    "sLengthMenu": "_MENU_",
                                    "searchPlaceholder": "<?php echo esc_attr($params['search']); ?>",
                                    "paginate": {
                                        "next": "»",
                                        "previous": "«"
                                    }
                                }
                            });
                        });
                    </script>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
//mod end


        function render_widget($params, $data){
			wp_enqueue_style( 'covid' );
			$all_options = get_option( 'covid_options' );
			$getFData=new stdClass();$getFData->cases=0;$getFData->deaths=0;$getFData->recovered=0;$getFData->todayCases=0;$getFData->todayDeaths=0;$getFData->active=0;if(is_array($data)){foreach($data as $key=>$value){$getFData->cases+=$value->cases;$getFData->deaths+=$value->deaths;$getFData->recovered+=$value->recovered;$getFData->todayCases+=$value->todayCases;$getFData->todayDeaths+=$value->todayDeaths;$getFData->active+=$value->active;}}else {$getFData->cases+=$data->cases;$getFData->deaths+=$data->deaths;$getFData->recovered+=$data->recovered;$getFData->todayCases+=isset($data->todayCases)?$data->todayCases:0;$getFData->todayDeaths+=isset($data->todayDeaths)?$data->todayDeaths:0;$getFData->active+=isset($data->active)?$data->active:0;}
			ob_start();
			?>
			<div class="covid19-card <?php echo $all_options['cov_theme']; ?> <?php if ((isset($all_options['cov_rtl']) ? $all_options['cov_rtl'] : null) == 'on') echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font']; ?>">
				<h4 class="covid19-title-big"><?php echo esc_html(isset($params['title_widget']) ? $params['title_widget'] : ''); ?></h4>
				<div class="covid19-row">
					<div class="covid19-col covid19-confirmed">
						<div class="covid19-num"><?php echo number_format($getFData->cases); ?></div>
						<div class="covid19-title"><?php echo esc_html($params['confirmed_title']); ?></div>
					</div>
					<div class="covid19-col covid19-deaths">
						<div class="covid19-num"><?php echo number_format($getFData->deaths); ?></div>
						<div class="covid19-title"><?php echo esc_html($params['deaths_title']); ?></div>
					</div>
					<div class="covid19-col covid19-recovered">
						<div class="covid19-num">
							<?php if (isset($getFData->recovered) && $getFData->recovered <= 0) {
								echo '–';
							} else {
								echo number_format($getFData->recovered);
							} ?></div>
						<div class="covid19-title"><?php echo esc_html($params['recovered_title']); ?></div>
					</div>
				</div>
			</div>
		<?php
			return ob_get_clean();
		}

		function render_line($params, $data){
			wp_enqueue_style('covid');
			$all_options = get_option('covid_options');
			ob_start();
		?>
			<span class="covid19-value">
				<?php echo esc_html($params['confirmed_title']); ?> <?php echo number_format($data->cases); ?>, <?php echo esc_html($params['deaths_title']); ?> <?php echo number_format($data->deaths); ?>, <?php echo esc_html($params['recovered_title']); ?> <?php echo number_format($data->recovered); ?>
			</span>
		<?php
			return ob_get_clean();
		}

        function render_banner_state($params, $data){
            wp_enqueue_style('covid');
            $dataAll = get_option('nycreatisAL');
            $all_options = get_option('covid_options');
            ob_start();
            ?>
            <div class="covid19-creep position-<?php echo esc_attr($params['position'] ? $params['position'] : 'bottom'); ?> <?php echo $all_options['cov_theme']; ?> <?php if ((isset($all_options['cov_rtl']) ? $all_options['cov_rtl'] : null) == 'on') echo 'rtl_enable'; ?> nosel" style="font-family:<?php echo $all_options['cov_font']; ?>">
                <div class="covid19-creep-live"><?php echo esc_html($params['title']); ?></div>
                <div class="covid19-creep-ul">
                    <?php $nyc = 0;
                    foreach ($data as $key => $value) : if ($nyc++ > 29) break; ?>
                        <div class="covid19-creep-country">
                            <div class="covid19-creep-col covid19-creep-countrycol">
                                <?php if (isset($value->countryInfo->flag)) : ?>
                                    <span class="covid19-creep-country_flag" style="background:url(<?php echo esc_html($value->countryInfo->flag); ?>) center no-repeat;background-size:cover;"></span>
                                <?php endif; ?>
                                <span class="covid19-creep-col-country"><?php echo esc_html($value->state); ?></span>
                                <span class="covid19-creep-col-cases"><?php echo number_format_i18n($value->confirmed); ?></span>
                            </div>
                            <div class="covid19-creep-tooltip">
                                <div id="covid19-creep-tooltip_content">
                                    <div class="covid19-creep-tvalue"><b><?php echo esc_html($params['confirmed_title']); ?>:</b> <?php echo number_format_i18n($value->confirmed); ?></div>
                                    <div class="covid19-creep-tvalue"><b><?php echo esc_html($params['deaths_title']); ?>:</b> <?php echo number_format_i18n($value->deaths); ?></div>
                                    <div class="covid19-creep-tvalue"><b><?php echo esc_html($params['recovered_title']); ?>:</b>
                                        <?php if (isset($value->recovered) && $value->recovered <= 0) {
                                            echo '–';
                                        } else {
                                            echo number_format_i18n($value->recovered);
                                        } ?>
                                    </div>
                                    <div class="covid19-creep-tvalue"><b><?php echo esc_html($params['active_title']); ?>:</b> <?php echo number_format_i18n($value->active); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }function render_banner($params, $data){
        wp_enqueue_style('covid');
        $dataAll = get_option('nycreatisAL');
        $all_options = get_option('covid_options');
        ob_start();
        ?>
        <div class="covid19-creep position-<?php echo esc_attr($params['position'] ? $params['position'] : 'bottom'); ?> <?php echo $all_options['cov_theme']; ?> <?php if ((isset($all_options['cov_rtl']) ? $all_options['cov_rtl'] : null) == 'on') echo 'rtl_enable'; ?> nosel" style="font-family:<?php echo $all_options['cov_font']; ?>">
            <div class="covid19-creep-live"><?php echo esc_html($params['title']); ?></div>
            <div class="covid19-creep-ul">
                <?php $nyc = 0;
                foreach ($data as $key => $value) : if ($nyc++ > 29) break; ?>
                    <div class="covid19-creep-country">
                        <div class="covid19-creep-col covid19-creep-countrycol">
                            <?php if (isset($value->countryInfo->flag)) : ?>
                                <span class="covid19-creep-country_flag" style="background:url(<?php echo esc_html($value->countryInfo->flag); ?>) center no-repeat;background-size:cover;"></span>
                            <?php endif; ?>
                            <span class="covid19-creep-col-country"><?php echo esc_html(translateCountryName($value)); ?></span>
                            <span class="covid19-creep-col-cases"><?php echo number_format_i18n($value->cases); ?></span>
                        </div>
                        <div class="covid19-creep-tooltip">
                            <div id="covid19-creep-tooltip_content">
                                <div class="covid19-creep-tvalue"><b><?php echo esc_html($params['confirmed_title']); ?>:</b> <?php echo number_format_i18n($value->cases); ?></div>
                                <div class="covid19-creep-tvalue"><b><?php echo esc_html($params['deaths_title']); ?>:</b> <?php echo number_format_i18n($value->deaths); ?></div>
                                <div class="covid19-creep-tvalue"><b><?php echo esc_html($params['recovered_title']); ?>:</b>
                                    <?php if (isset($value->recovered) && $value->recovered <= 0) {
                                        echo '–';
                                    } else {
                                        echo number_format_i18n($value->recovered);
                                    } ?>
                                </div>
                                <div class="covid19-creep-tvalue"><b><?php echo esc_html($params['active_title']); ?>:</b> <?php echo number_format_i18n($value->active); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

		function render_ticker($params, $data){
			wp_enqueue_style('covid');
			$dataAll = get_option('nycreatisAL');
			$all_options = get_option('covid_options');
			ob_start();
		?>
			<div class="covid19-ticker covid19-ticker-style-<?php echo esc_attr($params['style'] ? $params['style'] : 'vertical'); ?> <?php echo $all_options['cov_theme']; ?> <?php if ((isset($all_options['cov_rtl']) ? $all_options['cov_rtl'] : null) == 'on') echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font']; ?>">
				<span><?php echo esc_html($params['ticker_title']); ?></span>
				<ul>
					<li><?php echo esc_html($params['confirmed_title']); ?>: <?php echo number_format($data->cases); ?></li>
					<li><?php echo esc_html($params['deaths_title']); ?>: <?php echo number_format($data->deaths); ?></li>
					<li><?php echo esc_html($params['recovered_title']); ?>: <?php echo number_format($data->recovered); ?></li>
				</ul>


			</div>
		<?php
			return ob_get_clean();
		}

		function render_roll($params, $data){
			wp_enqueue_style('covid');
			$dataAll = get_option('nycreatisAL');
			$all_options = get_option('covid_options');
			ob_start();
		?>
			<div class="covid19-roll <?php echo $all_options['cov_theme']; ?> <?php if ((isset($all_options['cov_rtl']) ? $all_options['cov_rtl'] : null) == 'on') echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font']; ?>">
				<div class="covid19-title-big"><?php echo esc_html(isset($params['title_widget']) ? $params['title_widget'] : ''); ?></div>
				<ul class="covid19-roll2">
					<li class="covid19-country aiByXc">
						<div class="covid19-country-stats covid19-head">
							<div class="covid19-col covid19-countrycol">
								<div class="covid19-label"><?php echo esc_html($params['country_title']); ?></div>
							</div>
							<div class="covid19-col covid19-confirmed">
								<div class="covid19-label"><?php echo esc_html($params['confirmed_title']); ?></div>
							</div>
							<div class="covid19-col covid19-deaths">
								<div class="covid19-label"><?php echo esc_html($params['deaths_title']); ?></div>
							</div>
							<div class="covid19-col covid19-recovered">
								<div class="covid19-label"><?php echo esc_html($params['recovered_title']); ?></div>
							</div>
						</div>
					</li>
					<?php foreach ($data as $key => $value) : ?>
						<li class="covid19-country">
							<div class="covid19-country-stats">
								<div class="covid19-col covid19-countrycol">
									<?php if (isset($value->countryInfo->flag)) : ?>
										<span class="country_flag" style="background:url(<?php echo esc_html($value->countryInfo->flag); ?>) center no-repeat;background-size:cover;"></span>
									<?php endif; ?>
									<?php echo esc_html(translateCountryName($value));; ?>
								</div>
								<div class="covid19-col covid19-confirmed">
									<div class="covid19-value"><?php echo number_format_i18n($value->cases); ?></div>
								</div>
								<div class="covid19-col covid19-deaths">
									<div class="covid19-value"><?php echo number_format_i18n($value->deaths); ?></div>
								</div>
								<div class="covid19-col covid19-recovered">
									<div class="covid19-value">
										<?php if (isset($value->recovered) && $value->recovered <= 0) {
											echo '–';
										} else {
											echo number_format_i18n($value->recovered);
										} ?>
									</div>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="covid19-country covid19-total">
					<div class="covid19-country-stats">
						<div class="covid19-col covid19-totalcol"><?php esc_html_e($params['total_title']); ?></div>
						<div class="covid19-col covid19-confirmed">
							<div class="covid19-value"><?php echo number_format_i18n($dataAll->cases); ?></div>
						</div>
						<div class="covid19-col covid19-deaths">
							<div class="covid19-value"><?php echo number_format_i18n($dataAll->deaths); ?></div>
						</div>
						<div class="covid19-col covid19-recovered">
							<div class="covid19-value"><?php echo number_format_i18n($dataAll->recovered); ?></div>
						</div>
					</div>
				</div>
			</div>
		<?php
			return ob_get_clean();
		}

		function render_sheet($params, $data){
			wp_enqueue_style('covid');
			wp_enqueue_style('jquery.datatables');
			wp_enqueue_script('jquery.datatables');
			$uniqId = 'covid_table_' . md5(uniqid(rand(), 1));
			$all_options = get_option('covid_options');
			ob_start();
		?>
			<div class="table100 ver1 <?php echo $all_options['cov_theme']; ?> <?php if ((isset($all_options['cov_rtl']) ? $all_options['cov_rtl'] : null) == 'on') echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font']; ?>">
				<div class="covid19-sheet table100-nextcols">
					<table class="nowrap" id="<?php echo esc_attr($uniqId); ?>" data-page-length="<?php echo esc_attr($params['rows']); ?>" role="grid" style="width:100%" width="100%">
						<thead>
							<tr class="row100 head">
								<th class="cell100 column2 country_title"><?php echo esc_html($params['country_title']); ?></th>
								<th class="cell100 column3 confirmed_title"><?php echo esc_html($params['confirmed_title']); ?></th>
								<th class="cell100 column5 today_cases"><?php echo esc_html($params['today_cases']); ?></th>
								<th class="cell100 column6 deaths_title"><?php echo esc_html($params['deaths_title']); ?></th>
								<th class="cell100 column7 today_deaths"><?php echo esc_html($params['today_deaths']); ?></th>
								<th class="cell100 column8 today_deaths">%</th>
								<th class="cell100 column9 recovered_title"><?php echo esc_html($params['recovered_title']); ?></th>
								<th class="cell100 column10 recovered_title">%</th>
								<th class="cell100 column11 active_title"><?php echo esc_html($params['active_title']); ?></th>
								<th class="cell100 column12 tests_title"><?php echo esc_html($params['tests_title']); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($data as $key => $value) : ?>
								<tr class="row100 body">
									<td class="cell100 column2 Ncrts-<?php $arr = explode(' ', trim(translateCountryName($value))); echo $arr[0]; ?> country_title" data-label="<?php echo esc_html($params['country_title']); ?>" title="<?php echo esc_html(translateCountryName($value));; ?>" ncrts-country="<?php echo esc_html(translateCountryName($value));; ?>">
										<?php if (isset($value->countryInfo->flag)) : ?>
											<span class="country_flag" style="background:url(<?php echo esc_html($value->countryInfo->flag); ?>) center no-repeat;background-size:cover;"></span>
										<?php endif; ?>
										<?php echo esc_html(translateCountryName($value)); ?></td>
									<td class="cell100 column3 confirmed_title" data-label="<?php echo esc_html($params['confirmed_title']); ?>"><?php echo number_format($value->cases); ?></td>
									<td class="cell100 column5 today_cases" data-label="<?php echo esc_html($params['today_cases']); ?>"><?php echo number_format($value->todayCases); ?></td>
									<td class="cell100 column6 deaths_title" data-label="<?php echo esc_html($params['deaths_title']); ?>"><?php echo number_format($value->deaths); ?></td>
									<td class="cell100 column7 today_deaths" data-label="<?php echo esc_html($params['today_deaths']); ?>"><?php echo number_format($value->todayDeaths); ?></td>
									<td class="cell100 column8 percent_d"><?php echo round(($value->deaths) / ($value->cases) * 100, 1); ?>%</td>
									<td class="cell100 column9 recovered_title" data-label="<?php echo esc_html($params['recovered_title']); ?>"><?php if (isset($value->recovered) && $value->recovered <= 0) {echo '–';} else {echo number_format($value->recovered);} ?></td>
									<td class="cell100 column10 recovered_d"><?php if (isset($value->recovered) && $value->recovered <= 0) {echo '–';} else {echo round(($value->recovered) / ($value->cases) * 100, 1) . '%';} ?></td>
									<td class="cell100 column11 active_title" data-label="<?php echo esc_html($params['active_title']); ?>"><?php echo number_format($value->active); ?></td>
									<td class="cell100 column12 tests_title" data-label="<?php echo esc_html($params['tests_title']); ?>"><?php if (isset($value->tests) && $value->tests <= 0) {echo 'N/A';} else {echo number_format($value->tests);} ?></td>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<script>
						jQuery(document).ready(function($) {
							$('#<?php echo esc_attr($uniqId); ?>').DataTable({
								"scrollX": false,
								"responsive": true,
								"fixedColumns": true,
								"bInfo": false,
								"lengthMenu": [[10, 20, 50, 100],[10, 20, 50, 100]],
								"order": [[1, "desc"]],
								"searching": true,
								"language": {
									"url": "<?php echo esc_url($params['lang_url']); ?>",
									"search": "_INPUT_",
									"sLengthMenu": "_MENU_",
									"searchPlaceholder": "<?php echo esc_attr($params['search']); ?>",
									"paginate": {
										"next": "»",
										"previous": "«"
									}
								}
							});
						});
					</script>
				</div>
			</div>
		<?php
			return ob_get_clean();
		}

		/**
		function render_sheet_country($params, $data){
			wp_enqueue_style( 'covid' );
			wp_enqueue_style( 'jquery.datatables' );
			wp_enqueue_script( 'jquery.datatables' );
			$uniqId = 'covid_table_'.md5(uniqid(rand(),1));
			$all_options = get_option( 'covid_options' );
			ob_start();
			?>
				<div class="table100 ver1 <?php echo $all_options['cov_theme'];?> <?php if((isset($all_options['cov_rtl']) ? $all_options['cov_rtl'] : null)=='on') echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font'];?>">
				<div class="covid19-sheet table100-nextcols">
					<table class="nowrap" id="<?php echo esc_attr($uniqId); ?>" data-page-length="<?php echo esc_attr($params['showing']); ?>" role="grid" style="width:100%" width="100%">
						<thead>
							<tr class="row100 head">
								<th class="cell100 column2 country_title"><?php echo esc_html($params['country_title']); ?></th>
								<th class="cell100 column3 confirmed_title"><?php echo esc_html($params['confirmed_title']); ?></th>
								<th class="cell100 column6 deaths_title"><?php echo esc_html($params['deaths_title']); ?></th>
								<th class="cell100 column8 today_deaths">%</th>
								<th class="cell100 column9 recovered_title"><?php echo esc_html($params['recovered_title']); ?></th>
								<th class="cell100 column10 recovered_title">%</th>
								<th class="cell100 column11 active_title"><?php echo esc_html($params['active_title']); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($data as $key => $value) : ?>
							<tr class="row100 body">
								<td class="cell100 column2 ncrts-country_title" title="<?php echo esc_html($value->displayName); ?>"><?php echo esc_html($value->displayName); ?></td>
								<td class="cell100 column3 confirmed_title" data-label="<?php echo esc_html($params['confirmed_title']); ?>"><?php echo number_format($value->totalConfirmed); ?></td>
								<td class="cell100 column6 deaths_title" data-label="<?php echo esc_html($params['deaths_title']); ?>"><?php echo number_format($value->totalDeaths); ?></td>
								<td class="cell100 column8 percent_d"><?php echo round(($value->totalDeaths)/($value->totalConfirmed)*100, 1); ?>%</td>
								<td class="cell100 column9 recovered_title" data-label="<?php echo esc_html($params['recovered_title']); ?>"><?php echo number_format($value->totalRecovered); ?></td>
								<td class="cell100 column10 recovered_d"><?php echo round(($value->totalRecovered)/($value->totalConfirmed)*100, 1); ?>%</td>
								<td class="cell100 column11 active_title" data-label="<?php echo esc_html($params['active_title']); ?>"><?php echo round(($value->totalConfirmed)-($value->totalRecovered)-($value->totalDeaths)); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<script>
						jQuery(document).ready(function($) {
							$('#<?php echo esc_attr($uniqId); ?>').DataTable({
								"scrollX": false,
								"responsive": true,
								"fixedColumns": true,
								"bInfo" : false,
								"order": [[ 1, "desc" ]],
								"searching": <?php echo esc_attr($params['search']); ?>,
								"language": {
									"url": "<?php echo esc_url($params['lang_url']); ?>",
									"search": "_INPUT_",
									"sLengthMenu": "_MENU_",
									"searchPlaceholder": "Search...",
									"paginate": {
										"next": "»",
										"previous": "«"
									}
								}
							});
						});
					</script>
				</div>
				</div>
			<?php
			return ob_get_clean();
		}
		 **/

		/**
		 * Settings page
		 */
		function true_option_page(){
			global $true_page;
		?><div id="ncrts-admin-container">
				<div class="grid-x grid-container grid-padding-y admin-settings">
					<div class="cell small-12">
						<div class="callout">
							<h2><?php echo esc_html__('COVID-19 Options', 'covid'); ?><span class="v">2.2.5</span></h2>
							<p><?php echo esc_html__('The plugin allows adding statistics table/widgets via shortcode to inform site visitors about changes in the situation about Coronavirus pandemic.', 'covid'); ?></p>
						</div>
						<div class="tabs-content grid-x" data-tabs-content="setting-tabs">
							<div class="tabs-panel is-active" id="options" role="tabpanel" aria-labelledby="options-label">
								<!--<div class="notify"></div>-->
								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<form method="post" enctype="multipart/form-data" action="options.php">
										<?php
										settings_fields('covid_options');
										do_settings_sections($true_page);
										?>
										<p class="submit">
											<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
										</p>
									</form>
								</div>

								<?php $data = get_option('nycreatisCC'); ?>
								<div id="id01" class="modal grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-6">
										<div class="small-12 cell">
											<h3><?php esc_html_e('Widget Shortcode', 'covid'); ?></h3>
										</div>
										<div class="small-12 cell"><?php _e('<b>land:</b> NorthAmerica / SouthAmerica / Africa / Asia / Europe / Oceania', 'covid'); ?>.</div>
										<div class="small-12 cell">
											<h4><?php esc_html_e('Countries:', 'covid'); ?></h4>
										</div>
										<div class="small-12 cell">
											<select name="covid_country">
												<option value=""><?php esc_html_e('All Countries - Worldwide Statistics', 'covid'); ?></option>
												<?php
												foreach ($data as $item) {
													echo '<option value="' . $item->country . '">' . translateCountryName($item) . '</option>';
												}
												?>
											</select>
										</div>
										<p id="covidsh" class="covid_shortcode"><?php esc_html_e('[COVID19-WIDGET title_widget="Worldwide" land="" confirmed_title="Cases" deaths_title="Deaths" recovered_title="Recovered"]', 'covid'); ?></p>
									</div>
									<div class="small-6">
										<div class="small-12 cell">
											<h3><?php esc_html_e('Widget Shortcode: Full format', 'covid'); ?></h3>
										</div>
										<div class="small-12 cell"><?php _e('<b>land:</b> NorthAmerica / SouthAmerica / Africa / Asia / Europe / Oceania', 'covid'); ?>.</div>
										<div class="small-12 cell">
											<h4><?php esc_html_e('Countries:', 'covid'); ?></h4>
										</div>
										<div class="small-12 cell">
											<select name="covid_country_full">
												<option value=""><?php esc_html_e('All Countries - Worldwide Statistics', 'covid'); ?></option>
												<?php
												foreach ($data as $item) {
													echo '<option value="' . $item->country . '">' . translateCountryName($item) . '</option>';
												}
												?>
											</select>
										</div>
										<p id="covidsh-full" class="covid_shortcode"><?php esc_html_e('[COVID19-WIDGET title_widget="Worldwide" format="full" land="" confirmed_title="Cases" deaths_title="Deaths" recovered_title="Recovered" active_title="Active" today_cases="24h" today_deaths="24h"]', 'covid'); ?></p>
									</div>
								</div>

								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('Slip Card', 'covid'); ?></h3>
									</div>
									<div class="small-12 cell"><small><?php _e('Paste this shortcode into <b>Posts or Pages</b>.', 'covid'); ?></small></div>
									<div class="small-12 cell">
										<h4><?php esc_html_e('Countries:', 'covid'); ?></h4>
									</div>
									<div class="small-12 cell">
										<select name="covid_country_slip">
											<?php
											foreach ($data as $item) {
												echo '<option value="' . $item->country . '">' . translateCountryName($item) . '</option>';
											}
											?>
										</select>
									</div>
									<p id="covidsh-slip" class="covid_shortcode"><?php _e('[COVID19-SLIP country="USA" covid_title="Coronavirus" confirmed_title="Confirmed" deaths_title="Deaths" recovered_title="Recovered" active_title="Active" today_title="24h" world_title="World"]', 'covid'); ?></p>
								</div>

								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('Map of Countries', 'covid'); ?></h3>
									</div>
									<div class="small-12 cell"><?php _e('<b>Color:</b> red / blue / orange', 'covid'); ?></div>
									<div class="small-12 cell">
										<div id="covid19">
											<p id="covidsh" class="covid_shortcode"><?php esc_html_e('[COVID19 color="red" confirmed_title="Cases" deaths_title="Deaths" recovered_title="Recovered"]', 'covid'); ?></p>
										</div>
									</div>
								</div>

								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('Map of the USA', 'covid'); ?></h3>
									</div>
									<div class="small-12 cell"><?php _e('<b>Color:</b> red / blue / orange', 'covid'); ?></div>
									<div class="small-12 cell">
										<div id="covid19">
											<p id="covidsh" class="covid_shortcode"><?php esc_html_e('[COVID19-MAPUS color="red" confirmed_title="Confirmed" deaths_title="Deaths" active_title="Active"]', 'covid'); ?></p>
										</div>
									</div>
								</div>

								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('List of Countries', 'covid'); ?></h3>
									</div>
									<div class="small-12 cell"><small><?php _e('Paste this shortcode into <b>Posts or Pages</b>.', 'covid'); ?></small></div>
									<div class="small-12 cell">
										<div id="covid19">
											<p id="covidsh" class="covid_shortcode"><?php esc_html_e('[COVID19-ROLL title_widget="Worldwide" total_title="Total" country_title="Country" confirmed_title="Cases" deaths_title="Deaths" recovered_title="Recovered"]', 'covid'); ?></p>
										</div>
									</div>
								</div>

								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('Graph', 'covid'); ?></h3>
									</div>
									<div class="small-12 cell"><small><?php _e('Paste this shortcode into <b>Posts or Pages</b>.', 'covid'); ?></small></div>
									<div class="small-12 cell">
										<h4><?php esc_html_e('Countries:', 'covid'); ?></h4>
									</div>
									<div class="small-12 cell">
										<select name="covid_country_graph">
											<option value=""><?php esc_html_e('All Countries - Worldwide Statistics', 'covid'); ?></option>
											<?php
											foreach ($data as $item) {
												echo '<option value="' . $item->country . '">' . translateCountryName($item) . '</option>';
											}
											?>
										</select>
									</div>
									<p id="covidsh-graph" class="covid_shortcode"><?php _e('[COVID19-GRAPH title="World History Chart" confirmed_title="Confirmed" deaths_title="Deaths" recovered_title="Recovered"]', 'covid'); ?></p>
								</div>

								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('Table of Countries', 'covid'); ?></h3>
									</div>
									<div class="small-12 cell"><?php _e('<b>rows:</b> 10 / 20 / 50 / 100 (number of countries)', 'covid'); ?>.</div>
									<div class="small-12 cell"><?php _e('<b>search:</b> "Search by Country" in your language', 'covid'); ?>.</div>
									<div class="small-12 cell"><?php _e('<b>land:</b> NorthAmerica / SouthAmerica / Africa / Asia / Europe / Oceania', 'covid'); ?>.</div>
									<div class="small-12 cell">
										<div id="covid19">
											<p id="covidsh" class="covid_shortcode"><?php esc_html_e('[COVID19-SHEET country_title="Country" land="" rows="20" search="Search by Country..." confirmed_title="Cases" today_cases="24h" deaths_title="Deaths" today_deaths="24h" recovered_title="Recovered" active_title="Active"]', 'covid'); ?></p>
										</div>
									</div>
								</div>

								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('Banner Data', 'covid'); ?></h3>
									</div>
									<div class="small-12 cell"><?php _e('<b>position:</b> bottom / top', 'covid'); ?></div>
									<div class="small-12 cell">
										<div id="covid19">
											<p id="covidsh" class="covid_shortcode"><?php esc_html_e('[COVID19-BANNER position="bottom" confirmed_title="Cases" deaths_title="Deaths" recovered_title="Recovered" active_title="Active"]', 'covid'); ?></p>
										</div>
									</div>
								</div>

								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('Data Ticker', 'covid'); ?></h3>
									</div>
									<div class="small-12 cell"><small><?php _e('Paste this shortcode into <b>Sidebar Text widget</b>.', 'covid'); ?></small></div>
									<div class="small-12 cell"><?php _e('Use <b>style="horizontal"</b> for Horizontal style.', 'covid'); ?></div>
									<div class="small-12 cell">
										<h4><?php esc_html_e('Countries:', 'covid'); ?></h4>
									</div>
									<div class="small-12 cell">
										<select name="covid_country_ticker">
											<option value=""><?php esc_html_e('All Countries - Worldwide Statistics', 'covid'); ?></option>
											<?php
											foreach ($data as $item) {
												echo '<option value="' . $item->country . '">' . translateCountryName($item) . '</option>';
											}
											?>
										</select>
									</div>
									<p id="covidsh-ticker" class="covid_shortcode"><?php _e('[COVID19-TICKER ticker_title="World" style="vertical" confirmed_title="Confirmed" deaths_title="Deaths" recovered_title="Recovered"]', 'covid'); ?></p>
								</div>

								<div class="grid-x display-required callout" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('Inline Text data', 'covid'); ?></h3>
									</div>
									<div class="small-12 cell"><small><?php _e('Paste this shortcode into <b>text</b>.', 'covid'); ?></small></div>
									<div class="small-12 cell">
										<h4><?php esc_html_e('Countries:', 'covid'); ?></h4>
									</div>
									<div class="small-12 cell">
										<select name="covid_country_line">
											<option value=""><?php esc_html_e('All Countries - Worldwide Statistics', 'covid'); ?></option>
											<?php
											foreach ($data as $item) {
												echo '<option value="' . $item->country . '">' . translateCountryName($item) . '</option>';
											}
											?>
										</select>
									</div>
									<p id="covidsh-line" class="covid_shortcode"><?php _e('[COVID19-LINE confirmed_title="cases" deaths_title="deaths" recovered_title="recovered"]', 'covid'); ?></p>
								</div>

								<div class="display-required callout primary" style="opacity: 1; pointer-events: inherit;">
									<div class="small-12 cell">
										<h3><?php esc_html_e('What do the terms mean?', 'covid'); ?></h3>
									</div>
									<p><b><?php esc_html_e('Confirmed', 'covid'); ?></b>: <?php esc_html_e('The number of confirmed (recorded) cases', 'covid'); ?>.</p>
									<p><b><?php esc_html_e('Active', 'covid'); ?></b>: <?php esc_html_e('The number of confirmed cases that are still infected (Active = Confirmed - Deaths - Recovered)', 'covid'); ?>.</p>
									<p><b><?php esc_html_e('Deaths', 'covid'); ?></b>: <?php esc_html_e('The number of confirmed cases that have died', 'covid'); ?>.</p>
									<p><b><?php esc_html_e('Recovered', 'covid'); ?></b>: <?php esc_html_e('The number of confirmed cases that have recovered', 'covid'); ?>.</p>
									<hr>
									<div class="small-12 cell">
										<h3><?php esc_html_e('What do the columns in the table mean?', 'covid'); ?></h3>
									</div>
									<p><b><?php esc_html_e('24h', 'covid'); ?></b>: <?php esc_html_e('The amount of new data in last 24 hours', 'covid'); ?>.</p>
									<p><b><?php esc_html_e('Tests', 'covid'); ?></b>: <?php esc_html_e('Coronavirus Testing', 'covid'); ?>.</p>
									<p><b><?php esc_html_e('%', 'covid'); ?></b> : <?php esc_html_e('Percentage of Deaths or Recovered or Active in Confirmed Cases', 'covid'); ?>.</p>
									<p><b><?php esc_html_e('–', 'covid'); ?></b> : <?php esc_html_e('If there is no such data or 0, returns the empty string', 'covid'); ?>.</p>
									<hr>
									<div class="small-12 cell">
										<h3><?php esc_html_e('Data Sources', 'covid'); ?></h3>
									</div>
									<p><?php esc_html_e('WHO, CDC, ECDC, NHC, JHU CSSE, DXY & QQ', 'covid'); ?>.</p>
								</div>

							</div>
						</div>
					</div>
				</div>
			</div>
<?php
		}

		function nycreatis_enqueues(){
			$covid_options = get_option('covid_options');
			wp_enqueue_style('nycreatis_style', COVID_NYCREATIS_URL . 'assets/style.css', array(), COVID_NYCREATIS_VER);
			$nycreatis_custom_css = "{$covid_options['cov_css']}";
			wp_add_inline_style('nycreatis_style', $nycreatis_custom_css);
		}
	}
	new CovidNycreatis();
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'covid_add_plugin_page_contact_link');
function covid_add_plugin_page_contact_link( $links ) {
	$links[] = '<a href="http://1.envato.market/CovidHelp" target="_blank">' . __('Get Help') . '</a>';
	return $links;
}

function translateCountryName($c){
    if (isset($c->countryInfo->iso2) && isset(COVID_NYCREATIS_CL[$c->countryInfo->iso2]))
        return COVID_NYCREATIS_CL[$c->countryInfo->iso2];
    else
        return $c->country;
}
function translateCountryNameState($c){
    if (isset($c->countryInfo->iso2) && isset(COVID_NYCREATIS_CL[$c->countryInfo->iso2]))
        return COVID_NYCREATIS_CL[$c->countryInfo->iso2];
    else
        return $c->country;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'covid_add_plugin_page_settings_link');
function covid_add_plugin_page_settings_link($links){
	$links[] = '<a href="' .
		admin_url('options-general.php?page=covid-plugin-options') .
		'">' . __('Settings') . '</a>';
	return $links;
}

function true_option_settings(){
	global $true_page;
	// ( true_validate_settings() )
	register_setting('covid_options', 'covid_options', 'true_validate_settings');

	// Add section
	add_settings_section('true_section_1', esc_html__('Customization', 'covid'), '', $true_page);

	$true_field_params = array(
		'type'      => 'text',
		'id'        => 'cov_title',
		'default'	=> esc_html__('An interactive web-based dashboard to track COVID-19 in real time.', 'covid'),
		'placeholder'		=> 'An interactive web-based dashboard to track COVID-19 in real time.',
		'desc'      => '',
		'label_for' => 'cov_title'
	);
	add_settings_field('my_text_field', esc_html__('Worldwide Map Title', 'covid'), 'true_option_display_settings', $true_page, 'true_section_1', $true_field_params);

	$true_field_params = array(
		'type'      => 'textarea',
		'id'        => 'cov_desc',
		'default'	=> esc_html__('To identify new cases, we monitor various twitter feeds, online news services, and direct communication sent through the dashboard.', 'covid'),
		'desc'      => '',
		'label_for' => 'cov_desc'
	);
	add_settings_field('cov_desc_field', esc_html__('Worldwide Map Subtitle', 'covid'), 'true_option_display_settings', $true_page, 'true_section_1', $true_field_params);

	//	$true_field_params = array(
	//		'type'      => 'checkbox',
	//		'id'        => 'cov_countries_hide',
	//		'desc'      => esc_html__( 'Hide', 'covid' )
	//	);
	//	add_settings_field( 'cov_countries_hide_field', esc_html__( 'List of countries', 'covid' ), 'true_option_display_settings', $true_page, 'true_section_2', $true_field_params );

	//	$true_field_params = array(
	//		'type'      => 'checkbox',
	//		'id'        => 'cov_map_hide',
	//		'desc'      => esc_html__( 'Hide', 'covid' )
	//	);
	//	add_settings_field( 'cov_map_hide_field', esc_html__( 'Worldwide Map', 'covid' ), 'true_option_display_settings', $true_page, 'true_section_2', $true_field_params );

	$true_field_params = array(
		'type'      => 'select',
		'id'        => 'cov_theme',
		'desc'      => '',
		'vals'		=> array(esc_html__('Dark', 'covid') => 'dark_theme', esc_html__('Light', 'covid') => 'light_theme'),
		'label_for' => 'cov_theme'
	);
	add_settings_field('cov_theme_field', esc_html__('Theme', 'covid'), 'true_option_display_settings', $true_page, 'true_section_1', $true_field_params);

	$true_field_params = array(
		'type'      => 'select',
		'id'        => 'cov_font',
		'desc'      => '',
		'label_for' => 'cov_font',
		'vals'		=> array('Default' => '-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Ubuntu,Helvetica Neue,sans-serif', 'As on the website' => 'inherit', 'Arial, Helvetica' => 'Arial,Helvetica,sans-serif', 'Tahoma, Geneva' => 'Tahoma,Geneva,sans-serif', 'Trebuchet MS, Helvetica' => 'Trebuchet MS, Helvetica,sans-serif', 'Verdana, Geneva' => 'Verdana,Geneva,sans-serif',  'Georgia' => 'Georgia,sans-serif', 'Palatino' => 'Palatino,sans-serif', 'Times New Roman' => 'Times New Roman,sans-serif')
	);
	add_settings_field('cov_font_field', esc_html__('Font', 'covid'), 'true_option_display_settings', $true_page, 'true_section_1', $true_field_params);

	$true_field_params = array(
		'type'      => 'textarea',
		'id'        => 'cov_css',
		'default'	=> null,
		'desc'      => esc_html__('Without &lt;style&gt; tags', 'covid'),
		'label_for' => 'cov_css'
	);
	add_settings_field('cov_css_field', esc_html__('Custom CSS', 'covid'), 'true_option_display_settings', $true_page, 'true_section_1', $true_field_params);

	$true_field_params = array(
		'type'      => 'checkbox',
		'id'        => 'cov_rtl',
		'desc'      => esc_html__('Enable', 'covid'),
		'label_for' => 'cov_rtl'
	);
	add_settings_field('cov_rtl_field', esc_html__('Right-to-Left support', 'covid'), 'true_option_display_settings', $true_page, 'true_section_1', $true_field_params);

	$true_field_params = array(
		'type'      => 'select',
		'id'        => 'cov_tocl',
		'desc'      => 'The locale to use when translating country names. (ISO 3166-1)',
		'label_for' => 'cov_tocl',
		'vals'		=> array('af' => 'af', 'af_NA' => 'af_NA', 'af_ZA' => 'af_ZA', 'ak' => 'ak', 'ak_GH' => 'ak_GH', 'am' => 'am', 'am_ET' => 'am_ET', 'ar' => 'ar', 'ar_AE' => 'ar_AE', 'ar_BH' => 'ar_BH', 'ar_DJ' => 'ar_DJ', 'ar_DZ' => 'ar_DZ', 'ar_EG' => 'ar_EG', 'ar_EH' => 'ar_EH', 'ar_ER' => 'ar_ER', 'ar_IL' => 'ar_IL', 'ar_IQ' => 'ar_IQ', 'ar_JO' => 'ar_JO', 'ar_KM' => 'ar_KM', 'ar_KW' => 'ar_KW', 'ar_LB' => 'ar_LB', 'ar_LY' => 'ar_LY', 'ar_MA' => 'ar_MA', 'ar_MR' => 'ar_MR', 'ar_OM' => 'ar_OM', 'ar_PS' => 'ar_PS', 'ar_QA' => 'ar_QA', 'ar_SA' => 'ar_SA', 'ar_SD' => 'ar_SD', 'ar_SO' => 'ar_SO', 'ar_SS' => 'ar_SS', 'ar_SY' => 'ar_SY', 'ar_TD' => 'ar_TD', 'ar_TN' => 'ar_TN', 'ar_YE' => 'ar_YE', 'as' => 'as', 'as_IN' => 'as_IN', 'az' => 'az', 'az_AZ' => 'az_AZ', 'az_Cyrl' => 'az_Cyrl', 'az_Cyrl_AZ' => 'az_Cyrl_AZ', 'az_Latn' => 'az_Latn', 'az_Latn_AZ' => 'az_Latn_AZ', 'be' => 'be', 'be_BY' => 'be_BY', 'bg' => 'bg', 'bg_BG' => 'bg_BG', 'bm' => 'bm', 'bm_Latn' => 'bm_Latn', 'bm_Latn_ML' => 'bm_Latn_ML', 'bm_ML' => 'bm_ML', 'bn' => 'bn', 'bn_BD' => 'bn_BD', 'bn_IN' => 'bn_IN', 'bo' => 'bo', 'bo_CN' => 'bo_CN', 'bo_IN' => 'bo_IN', 'br' => 'br', 'br_FR' => 'br_FR', 'bs' => 'bs', 'bs_BA' => 'bs_BA', 'bs_Cyrl' => 'bs_Cyrl', 'bs_Cyrl_BA' => 'bs_Cyrl_BA', 'bs_Latn' => 'bs_Latn', 'bs_Latn_BA' => 'bs_Latn_BA', 'ca' => 'ca', 'ca_AD' => 'ca_AD', 'ca_ES' => 'ca_ES', 'ca_FR' => 'ca_FR', 'ca_IT' => 'ca_IT', 'ce' => 'ce', 'ce_RU' => 'ce_RU', 'cs' => 'cs', 'cs_CZ' => 'cs_CZ', 'cy' => 'cy', 'cy_GB' => 'cy_GB', 'da' => 'da', 'da_DK' => 'da_DK', 'da_GL' => 'da_GL', 'de' => 'de', 'de_AT' => 'de_AT', 'de_BE' => 'de_BE', 'de_CH' => 'de_CH', 'de_DE' => 'de_DE', 'de_IT' => 'de_IT', 'de_LI' => 'de_LI', 'de_LU' => 'de_LU', 'dz' => 'dz', 'dz_BT' => 'dz_BT', 'ee' => 'ee', 'ee_GH' => 'ee_GH', 'ee_TG' => 'ee_TG', 'el' => 'el', 'el_CY' => 'el_CY', 'el_GR' => 'el_GR', 'en' => 'en', 'en_AE' => 'en_AE', 'en_AG' => 'en_AG', 'en_AI' => 'en_AI', 'en_AS' => 'en_AS', 'en_AT' => 'en_AT', 'en_AU' => 'en_AU', 'en_BB' => 'en_BB', 'en_BE' => 'en_BE', 'en_BI' => 'en_BI', 'en_BM' => 'en_BM', 'en_BS' => 'en_BS', 'en_BW' => 'en_BW', 'en_BZ' => 'en_BZ', 'en_CA' => 'en_CA', 'en_CC' => 'en_CC', 'en_CH' => 'en_CH', 'en_CK' => 'en_CK', 'en_CM' => 'en_CM', 'en_CX' => 'en_CX', 'en_CY' => 'en_CY', 'en_DE' => 'en_DE', 'en_DG' => 'en_DG', 'en_DK' => 'en_DK', 'en_DM' => 'en_DM', 'en_ER' => 'en_ER', 'en_FI' => 'en_FI', 'en_FJ' => 'en_FJ', 'en_FK' => 'en_FK', 'en_FM' => 'en_FM', 'en_GB' => 'en_GB', 'en_GD' => 'en_GD', 'en_GG' => 'en_GG', 'en_GH' => 'en_GH', 'en_GI' => 'en_GI', 'en_GM' => 'en_GM', 'en_GU' => 'en_GU', 'en_GY' => 'en_GY', 'en_HK' => 'en_HK', 'en_IE' => 'en_IE', 'en_IL' => 'en_IL', 'en_IM' => 'en_IM', 'en_IN' => 'en_IN', 'en_IO' => 'en_IO', 'en_JE' => 'en_JE', 'en_JM' => 'en_JM', 'en_KE' => 'en_KE', 'en_KI' => 'en_KI', 'en_KN' => 'en_KN', 'en_KY' => 'en_KY', 'en_LC' => 'en_LC', 'en_LR' => 'en_LR', 'en_LS' => 'en_LS', 'en_MG' => 'en_MG', 'en_MH' => 'en_MH', 'en_MO' => 'en_MO', 'en_MP' => 'en_MP', 'en_MS' => 'en_MS', 'en_MT' => 'en_MT', 'en_MU' => 'en_MU', 'en_MW' => 'en_MW', 'en_MY' => 'en_MY', 'en_NA' => 'en_NA', 'en_NF' => 'en_NF', 'en_NG' => 'en_NG', 'en_NL' => 'en_NL', 'en_NR' => 'en_NR', 'en_NU' => 'en_NU', 'en_NZ' => 'en_NZ', 'en_PG' => 'en_PG', 'en_PH' => 'en_PH', 'en_PK' => 'en_PK', 'en_PN' => 'en_PN', 'en_PR' => 'en_PR', 'en_PW' => 'en_PW', 'en_RW' => 'en_RW', 'en_SB' => 'en_SB', 'en_SC' => 'en_SC', 'en_SD' => 'en_SD', 'en_SE' => 'en_SE', 'en_SG' => 'en_SG', 'en_SH' => 'en_SH', 'en_SI' => 'en_SI', 'en_SL' => 'en_SL', 'en_SS' => 'en_SS', 'en_SX' => 'en_SX', 'en_SZ' => 'en_SZ', 'en_TC' => 'en_TC', 'en_TK' => 'en_TK', 'en_TO' => 'en_TO', 'en_TT' => 'en_TT', 'en_TV' => 'en_TV', 'en_TZ' => 'en_TZ', 'en_UG' => 'en_UG', 'en_UM' => 'en_UM', 'en_US' => 'en_US', 'en_VC' => 'en_VC', 'en_VG' => 'en_VG', 'en_VI' => 'en_VI', 'en_VU' => 'en_VU', 'en_WS' => 'en_WS', 'en_ZA' => 'en_ZA', 'en_ZM' => 'en_ZM', 'en_ZW' => 'en_ZW', 'eo' => 'eo', 'es' => 'es', 'es_AR' => 'es_AR', 'es_BO' => 'es_BO', 'es_BR' => 'es_BR', 'es_BZ' => 'es_BZ', 'es_CL' => 'es_CL', 'es_CO' => 'es_CO', 'es_CR' => 'es_CR', 'es_CU' => 'es_CU', 'es_DO' => 'es_DO', 'es_EA' => 'es_EA', 'es_EC' => 'es_EC', 'es_ES' => 'es_ES', 'es_GQ' => 'es_GQ', 'es_GT' => 'es_GT', 'es_HN' => 'es_HN', 'es_IC' => 'es_IC', 'es_MX' => 'es_MX', 'es_NI' => 'es_NI', 'es_PA' => 'es_PA', 'es_PE' => 'es_PE', 'es_PH' => 'es_PH', 'es_PR' => 'es_PR', 'es_PY' => 'es_PY', 'es_SV' => 'es_SV', 'es_US' => 'es_US', 'es_UY' => 'es_UY', 'es_VE' => 'es_VE', 'et' => 'et', 'et_EE' => 'et_EE', 'eu' => 'eu', 'eu_ES' => 'eu_ES', 'fa' => 'fa', 'fa_AF' => 'fa_AF', 'fa_IR' => 'fa_IR', 'ff' => 'ff', 'ff_CM' => 'ff_CM', 'ff_GN' => 'ff_GN', 'ff_Latn' => 'ff_Latn', 'ff_Latn_BF' => 'ff_Latn_BF', 'ff_Latn_CM' => 'ff_Latn_CM', 'ff_Latn_GH' => 'ff_Latn_GH', 'ff_Latn_GM' => 'ff_Latn_GM', 'ff_Latn_GN' => 'ff_Latn_GN', 'ff_Latn_GW' => 'ff_Latn_GW', 'ff_Latn_LR' => 'ff_Latn_LR', 'ff_Latn_MR' => 'ff_Latn_MR', 'ff_Latn_NE' => 'ff_Latn_NE', 'ff_Latn_NG' => 'ff_Latn_NG', 'ff_Latn_SL' => 'ff_Latn_SL', 'ff_Latn_SN' => 'ff_Latn_SN', 'ff_MR' => 'ff_MR', 'ff_SN' => 'ff_SN', 'fi' => 'fi', 'fi_FI' => 'fi_FI', 'fo' => 'fo', 'fo_DK' => 'fo_DK', 'fo_FO' => 'fo_FO', 'fr' => 'fr', 'fr_BE' => 'fr_BE', 'fr_BF' => 'fr_BF', 'fr_BI' => 'fr_BI', 'fr_BJ' => 'fr_BJ', 'fr_BL' => 'fr_BL', 'fr_CA' => 'fr_CA', 'fr_CD' => 'fr_CD', 'fr_CF' => 'fr_CF', 'fr_CG' => 'fr_CG', 'fr_CH' => 'fr_CH', 'fr_CI' => 'fr_CI', 'fr_CM' => 'fr_CM', 'fr_DJ' => 'fr_DJ', 'fr_DZ' => 'fr_DZ', 'fr_FR' => 'fr_FR', 'fr_GA' => 'fr_GA', 'fr_GF' => 'fr_GF', 'fr_GN' => 'fr_GN', 'fr_GP' => 'fr_GP', 'fr_GQ' => 'fr_GQ', 'fr_HT' => 'fr_HT', 'fr_KM' => 'fr_KM', 'fr_LU' => 'fr_LU', 'fr_MA' => 'fr_MA', 'fr_MC' => 'fr_MC', 'fr_MF' => 'fr_MF', 'fr_MG' => 'fr_MG', 'fr_ML' => 'fr_ML', 'fr_MQ' => 'fr_MQ', 'fr_MR' => 'fr_MR', 'fr_MU' => 'fr_MU', 'fr_NC' => 'fr_NC', 'fr_NE' => 'fr_NE', 'fr_PF' => 'fr_PF', 'fr_PM' => 'fr_PM', 'fr_RE' => 'fr_RE', 'fr_RW' => 'fr_RW', 'fr_SC' => 'fr_SC', 'fr_SN' => 'fr_SN', 'fr_SY' => 'fr_SY', 'fr_TD' => 'fr_TD', 'fr_TG' => 'fr_TG', 'fr_TN' => 'fr_TN', 'fr_VU' => 'fr_VU', 'fr_WF' => 'fr_WF', 'fr_YT' => 'fr_YT', 'fy' => 'fy', 'fy_NL' => 'fy_NL', 'ga' => 'ga', 'ga_IE' => 'ga_IE', 'gd' => 'gd', 'gd_GB' => 'gd_GB', 'gl' => 'gl', 'gl_ES' => 'gl_ES', 'gu' => 'gu', 'gu_IN' => 'gu_IN', 'gv' => 'gv', 'gv_IM' => 'gv_IM', 'ha' => 'ha', 'ha_GH' => 'ha_GH', 'ha_Latn' => 'ha_Latn', 'ha_Latn_GH' => 'ha_Latn_GH', 'ha_Latn_NE' => 'ha_Latn_NE', 'ha_Latn_NG' => 'ha_Latn_NG', 'ha_NE' => 'ha_NE', 'ha_NG' => 'ha_NG', 'he' => 'he', 'he_IL' => 'he_IL', 'hi' => 'hi', 'hi_IN' => 'hi_IN', 'hr' => 'hr', 'hr_BA' => 'hr_BA', 'hr_HR' => 'hr_HR', 'hu' => 'hu', 'hu_HU' => 'hu_HU', 'hy' => 'hy', 'hy_AM' => 'hy_AM', 'ia' => 'ia', 'id' => 'id', 'id_ID' => 'id_ID', 'ig' => 'ig', 'ig_NG' => 'ig_NG', 'ii' => 'ii', 'ii_CN' => 'ii_CN', 'is' => 'is', 'is_IS' => 'is_IS', 'it' => 'it', 'it_CH' => 'it_CH', 'it_IT' => 'it_IT', 'it_SM' => 'it_SM', 'it_VA' => 'it_VA', 'ja' => 'ja', 'ja_JP' => 'ja_JP', 'jv' => 'jv', 'jv_ID' => 'jv_ID', 'ka' => 'ka', 'ka_GE' => 'ka_GE', 'ki' => 'ki', 'ki_KE' => 'ki_KE', 'kk' => 'kk', 'kl' => 'kl', 'kl_GL' => 'kl_GL', 'km' => 'km', 'km_KH' => 'km_KH', 'kn' => 'kn', 'kn_IN' => 'kn_IN', 'ko' => 'ko', 'ko_KP' => 'ko_KP', 'ko_KR' => 'ko_KR', 'ks' => 'ks', 'ks_Arab' => 'ks_Arab', 'ks_Arab_IN' => 'ks_Arab_IN', 'ks_IN' => 'ks_IN', 'ku' => 'ku', 'ku_TR' => 'ku_TR', 'kw' => 'kw', 'kw_GB' => 'kw_GB', 'ky' => 'ky', 'ky_Cyrl' => 'ky_Cyrl', 'ky_Cyrl_KG' => 'ky_Cyrl_KG', 'ky_KG' => 'ky_KG', 'lb' => 'lb', 'lb_LU' => 'lb_LU', 'lg' => 'lg', 'lg_UG' => 'lg_UG', 'ln' => 'ln', 'ln_AO' => 'ln_AO', 'ln_CD' => 'ln_CD', 'ln_CF' => 'ln_CF', 'ln_CG' => 'ln_CG', 'lo' => 'lo', 'lo_LA' => 'lo_LA', 'lt' => 'lt', 'lt_LT' => 'lt_LT', 'lu' => 'lu', 'lu_CD' => 'lu_CD', 'lv' => 'lv', 'lv_LV' => 'lv_LV', 'mg' => 'mg', 'mg_MG' => 'mg_MG', 'mi' => 'mi', 'mi_NZ' => 'mi_NZ', 'mk' => 'mk', 'mk_MK' => 'mk_MK', 'ml' => 'ml', 'ml_IN' => 'ml_IN', 'mn' => 'mn', 'mn_Cyrl' => 'mn_Cyrl', 'mn_Cyrl_MN' => 'mn_Cyrl_MN', 'mn_MN' => 'mn_MN', 'mr' => 'mr', 'mr_IN' => 'mr_IN', 'ms' => 'ms', 'ms_BN' => 'ms_BN', 'ms_Latn' => 'ms_Latn', 'ms_Latn_BN' => 'ms_Latn_BN', 'ms_Latn_MY' => 'ms_Latn_MY', 'ms_Latn_SG' => 'ms_Latn_SG', 'ms_MY' => 'ms_MY', 'ms_SG' => 'ms_SG', 'mt' => 'mt', 'mt_MT' => 'mt_MT', 'my' => 'my', 'my_MM' => 'my_MM', 'nb' => 'nb', 'nb_NO' => 'nb_NO', 'nb_SJ' => 'nb_SJ', 'nd' => 'nd', 'nd_ZW' => 'nd_ZW', 'ne' => 'ne', 'ne_IN' => 'ne_IN', 'ne_NP' => 'ne_NP', 'nl' => 'nl', 'nl_AW' => 'nl_AW', 'nl_BE' => 'nl_BE', 'nl_BQ' => 'nl_BQ', 'nl_CW' => 'nl_CW', 'nl_NL' => 'nl_NL', 'nl_SR' => 'nl_SR', 'nl_SX' => 'nl_SX', 'nn' => 'nn', 'nn_NO' => 'nn_NO', 'no' => 'no', 'no_NO' => 'no_NO', 'om' => 'om', 'om_ET' => 'om_ET', 'om_KE' => 'om_KE', 'or' => 'or', 'or_IN' => 'or_IN', 'os' => 'os', 'os_GE' => 'os_GE', 'os_RU' => 'os_RU', 'pa' => 'pa', 'pa_Arab' => 'pa_Arab', 'pa_Arab_PK' => 'pa_Arab_PK', 'pa_Guru' => 'pa_Guru', 'pa_Guru_IN' => 'pa_Guru_IN', 'pa_IN' => 'pa_IN', 'pa_PK' => 'pa_PK', 'pl' => 'pl', 'pl_PL' => 'pl_PL', 'ps' => 'ps', 'ps_AF' => 'ps_AF', 'ps_PK' => 'ps_PK', 'pt' => 'pt', 'pt_AO' => 'pt_AO', 'pt_BR' => 'pt_BR', 'pt_CH' => 'pt_CH', 'pt_CV' => 'pt_CV', 'pt_GQ' => 'pt_GQ', 'pt_GW' => 'pt_GW', 'pt_LU' => 'pt_LU', 'pt_MO' => 'pt_MO', 'pt_MZ' => 'pt_MZ', 'pt_PT' => 'pt_PT', 'pt_ST' => 'pt_ST', 'pt_TL' => 'pt_TL', 'qu' => 'qu', 'qu_BO' => 'qu_BO', 'qu_EC' => 'qu_EC', 'qu_PE' => 'qu_PE', 'rm' => 'rm', 'rm_CH' => 'rm_CH', 'rn' => 'rn', 'rn_BI' => 'rn_BI', 'ro' => 'ro', 'ro_MD' => 'ro_MD', 'ro_RO' => 'ro_RO', 'ru' => 'ru', 'rw' => 'rw', 'rw_RW' => 'rw_RW', 'sd' => 'sd', 'sd_PK' => 'sd_PK', 'se' => 'se', 'se_FI' => 'se_FI', 'se_NO' => 'se_NO', 'se_SE' => 'se_SE', 'sg' => 'sg', 'sg_CF' => 'sg_CF', 'sh' => 'sh', 'sh_BA' => 'sh_BA', 'si' => 'si', 'si_LK' => 'si_LK', 'sk' => 'sk', 'sk_SK' => 'sk_SK', 'sl' => 'sl', 'sl_SI' => 'sl_SI', 'sn' => 'sn', 'sn_ZW' => 'sn_ZW', 'so' => 'so', 'so_DJ' => 'so_DJ', 'so_ET' => 'so_ET', 'so_KE' => 'so_KE', 'so_SO' => 'so_SO', 'sq' => 'sq', 'sq_AL' => 'sq_AL', 'sq_MK' => 'sq_MK', 'sq_XK' => 'sq_XK', 'sr' => 'sr', 'sr_BA' => 'sr_BA', 'sr_Cyrl' => 'sr_Cyrl', 'sr_Cyrl_BA' => 'sr_Cyrl_BA', 'sr_Cyrl_ME' => 'sr_Cyrl_ME', 'sr_Cyrl_RS' => 'sr_Cyrl_RS', 'sr_Cyrl_XK' => 'sr_Cyrl_XK', 'sr_Latn' => 'sr_Latn', 'sr_Latn_BA' => 'sr_Latn_BA', 'sr_Latn_ME' => 'sr_Latn_ME', 'sr_Latn_RS' => 'sr_Latn_RS', 'sr_Latn_XK' => 'sr_Latn_XK', 'sr_ME' => 'sr_ME', 'sr_RS' => 'sr_RS', 'sr_XK' => 'sr_XK', 'sv' => 'sv', 'sv_AX' => 'sv_AX', 'sv_FI' => 'sv_FI', 'sv_SE' => 'sv_SE', 'sw' => 'sw', 'sw_CD' => 'sw_CD', 'sw_KE' => 'sw_KE', 'sw_TZ' => 'sw_TZ', 'sw_UG' => 'sw_UG', 'ta' => 'ta', 'ta_IN' => 'ta_IN', 'ta_LK' => 'ta_LK', 'ta_MY' => 'ta_MY', 'ta_SG' => 'ta_SG', 'te' => 'te', 'te_IN' => 'te_IN', 'tg' => 'tg', 'tg_TJ' => 'tg_TJ', 'th' => 'th', 'th_TH' => 'th_TH', 'ti' => 'ti', 'ti_ER' => 'ti_ER', 'ti_ET' => 'ti_ET', 'tk' => 'tk', 'tk_TM' => 'tk_TM', 'tl' => 'tl', 'tl_PH' => 'tl_PH', 'to' => 'to', 'to_TO' => 'to_TO', 'tr' => 'tr', 'tr_CY' => 'tr_CY', 'tr_TR' => 'tr_TR', 'tt' => 'tt', 'tt_RU' => 'tt_RU', 'ug' => 'ug', 'ug_Arab' => 'ug_Arab', 'ug_Arab_CN' => 'ug_Arab_CN', 'ug_CN' => 'ug_CN', 'uk' => 'uk', 'uk_UA' => 'uk_UA', 'ur' => 'ur', 'ur_IN' => 'ur_IN', 'ur_PK' => 'ur_PK', 'uz' => 'uz', 'uz_AF' => 'uz_AF', 'uz_Arab' => 'uz_Arab', 'uz_Arab_AF' => 'uz_Arab_AF', 'uz_Cyrl' => 'uz_Cyrl', 'uz_Cyrl_UZ' => 'uz_Cyrl_UZ', 'uz_Latn' => 'uz_Latn', 'uz_Latn_UZ' => 'uz_Latn_UZ', 'uz_UZ' => 'uz_UZ', 'vi' => 'vi', 'vi_VN' => 'vi_VN', 'wo' => 'wo', 'wo_SN' => 'wo_SN', 'xh' => 'xh', 'xh_ZA' => 'xh_ZA', 'yi' => 'yi', 'yo' => 'yo', 'yo_BJ' => 'yo_BJ', 'yo_NG' => 'yo_NG', 'zh' => 'zh', 'zh_CN' => 'zh_CN', 'zh_HK' => 'zh_HK', 'zh_Hans' => 'zh_Hans', 'zh_Hans_CN' => 'zh_Hans_CN', 'zh_Hans_HK' => 'zh_Hans_HK', 'zh_Hans_MO' => 'zh_Hans_MO', 'zh_Hans_SG' => 'zh_Hans_SG', 'zh_Hant' => 'zh_Hant', 'zh_Hant_HK' => 'zh_Hant_HK', 'zh_Hant_MO' => 'zh_Hant_MO', 'zh_Hant_TW' => 'zh_Hant_TW', 'zh_MO' => 'zh_MO', 'zh_SG' => 'zh_SG', 'zh_TW' => 'zh_TW', 'zu' => 'zu', 'zu_ZA' => 'zu_ZA')
	);
	add_settings_field('cov_tocl_field', esc_html__('Locale', 'covid'), 'true_option_display_settings', $true_page, 'true_section_1', $true_field_params);
}
add_action('admin_init', 'true_option_settings');

/*
		 * Show fields
		 */
function true_option_display_settings($args){
	extract($args);

	$option_name = 'covid_options';

	$o = get_option($option_name);

	switch ($type) {
		case 'text':
			$o[$id] = esc_attr(stripslashes($o[$id]));
			echo "<input class='regular-text' type='text' id='$id' placeholder='$placeholder' name='" . $option_name . "[$id]' value='$o[$id]' />";
			echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
			break;
		case 'textarea':
			$o[$id] = esc_attr(stripslashes($o[$id]));
			echo "<textarea class='code regular-text' cols='12' rows='3' type='text' id='$id' name='" . $option_name . "[$id]'>$o[$id]</textarea>";
			echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
			break;
		case 'checkbox':
			$checked = isset($o[$id]) && ($o[$id] == 'on') ? " checked='on'" :  '';
			echo "<label><input type='checkbox' id='$id' name='" . $option_name . "[$id]' $checked /> ";
			echo ($desc != '') ? $desc : "";
			echo "</label>";
			break;
		case 'select':
			echo "<select id='$id' name='" . $option_name . "[$id]'>";
			if ($id == "cov_tocl" && !isset($o[$id]))
				$o[$id] = "en";
			foreach ($vals as $k => $l) {
				$selected = isset($o[$id]) && ($o[$id] == $l) ? "selected='selected'" : '';
				echo "<option value='$l' $selected>$k</option>";
			}
			echo ($desc != '') ? $desc : "";
			echo "</select>";
			echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
			break;
		case 'radio':
			echo "<fieldset>";
			foreach ($vals as $v => $l) {
				$checked = isset($o[$id]) && ($o[$id] == $v) ? "checked='on'" : '';
				echo "<label><input type='radio' name='" . $option_name . "[$id]' value='$v' $checked />$l</label><br />";
			}
			echo "</fieldset>";
			break;
	}
}

/*
* Check fields
*/
function true_validate_settings($input){
	foreach ($input as $k => $v) {
		$valid_input[$k] = trim($v);
	}
	return $valid_input;
}

function insert_jquery(){
	wp_enqueue_script('jquery', false, array(), false, false);
}
add_filter('wp_enqueue_scripts', 'insert_jquery', 1);
