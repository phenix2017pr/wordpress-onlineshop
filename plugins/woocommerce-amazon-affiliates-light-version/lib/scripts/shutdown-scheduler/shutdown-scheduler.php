<?php
// managing the shutdown callback events:
if(class_exists('aateamShutdownScheduler') != true) {
class aateamShutdownScheduler {
    private $callbacks; // array to store user callbacks
    
    public function __construct() {
        $this->callbacks = array();
        register_shutdown_function(array($this, 'callRegisteredShutdown'));
    }
    public function registerShutdownEvent() {
        $callback = func_get_args();
        
        if (empty($callback)) {
            trigger_error('No callback passed to '.__FUNCTION__.' method', E_USER_ERROR);
            return false;
        }
        if (!is_callable($callback[0])) {
            trigger_error('Invalid callback passed to the '.__FUNCTION__.' method', E_USER_ERROR);
            return false;
        }
        $this->callbacks[] = $callback;
        return true;
    }
    public function callRegisteredShutdown() {
        foreach ($this->callbacks as $arguments) {
            $callback = array_shift($arguments);
            call_user_func_array($callback, $arguments);
        }
    }
    // test methods:
    public function getLastError($ajax=false) {
		$__statRet = array(
			'status' => 'invalid',
			'msg' => ''
		);

		$err = error_get_last(); 
     	if ( $err==null ) {
     		$__statRet['msg'] = 'No errors';
     		if ( $ajax ) die(json_encode($__statRet));
			echo $__statRet['msg'];
		}
		else {
			ob_start();
			var_dump($err);
			$output = ob_get_contents();
			ob_end_clean();

			$__statRet['msg'] = $output;
     		if ( $ajax ) die(json_encode($__statRet));
			var_dump($__statRet['msg']);
		} 
    }
}
}
?>