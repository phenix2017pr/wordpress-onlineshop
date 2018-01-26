<?php
/**
 * aaRenderTime
 *
 * @license http://php.net/manual/en/function.microtime.php /JumpIfBelow user
 */

if ( !class_exists('aaRenderTime') ) {

class aaRenderTime{
        const VERSION = '1.0';
        const PRECISION_SECOND = 0;
        const PRECISION_MILLISECOND = 1;
        const PRECISION_MICROSECOND = 2;
    
        private $debug;
        private $start;
        private $end;
        
        public $the_plugin = null;
        static protected $_instance;


        public function __construct( $parent=null ){
            $this->the_plugin = $parent;

            $this->debug = false;
            $this->start = null;
            $this->end = null;
        }
        
        /**
        * Singleton pattern
        *
        * @return Singleton instance
        */
        static public function getInstance( $parent=null )
        {
            if (!self::$_instance) {
                self::$_instance = new self($parent);
            }
            
            return self::$_instance;
        }
    
        public function start(){
            // microtime: returns a float, which represents the current time in seconds since the Unix epoch accurate to the nearest microsecond.
            $this->debug = false;
            $this->start = microtime(true);
            $this->end = null;
        }
    
        public function end( $debug=false ){
            $this->debug = $debug;
            $this->end = microtime(true);
        }
    
        /**
        * This function return the time the code use to process
        * @param $precision the precision wanted, with const. second, millisecond and microsecond available (default PRECISION_SECOND)
        * @param $floatingPrecision the number of numbers after the floating point (default 0)
        * @param $showUnit precise if the unit should be returned (default true)
        * @return the render time in the precision asked. Note that the precision is ±0.5 the precision (eq. 5s is at least 4.5s and at most 5.5s)
        * The code have an error about 2 or 3µs (time to execute the end function)
        */
        public function getRenderTime($precision = 0, $floatingPrecision = 0, $showUnit = true){

            $test = is_int($precision) && $precision >= self::PRECISION_SECOND && $precision <= self::PRECISION_MICROSECOND &&
                is_float($this->start) && is_float($this->end) && $this->start <= $this->end &&
                is_int($floatingPrecision) && $floatingPrecision >= 0 &&
                is_bool($showUnit);

            if($test){
                $duration = round(($this->end - $this->start) * pow(10, ($precision * 3)), $floatingPrecision);

                if ( $this->debug ) {
                    var_dump('<pre>', $this->start, $this->end, $duration, '</pre>'); die('debug...'); 
                }

                if($showUnit)
                    return $duration.' '.$this->getUnit($precision);
                else
                    return $duration;
            }else{
                return 'Can\'t return the render time';
            }
        }
    
        public function getUnit($precision){
            switch($precision){
                case 0:
                    return 's';
                case 1:
                    return 'ms';
                case 2:
                    return 'µs';
                default :
                    return '(no unit)';
            }
        }
        
        // Simple function to replicate PHP 5 behaviour
        private function microtime_float(){
            list($usec, $sec) = explode(" ", microtime());
            return ((float)$usec + (float)$sec);
        }
    }
} // end CLASS definition

/*
    // CALL
    $render_time = new RenderTime();
    $render_time->start();
    for($i = 0; $i < 2 ** 24; $i++){}
    $render_time->end();
    echo 'Time to render: '.$render_time->getRenderTime(RenderTime::PRECISION_MILLISECOND);
*/  
?>