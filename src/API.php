<?php
namespace hlrcheck;

class API {
    
    protected $_key;
    protected $_secret;
    protected $_endpoint;
    
    protected function _url($uri,$data){
        $hash = urlencode(base64_encode(hash_hmac('sha1',$data.$uri,$this->_secret,true)));
        return $this->_endpoint.$uri."?key={$this->_key}&hash=$hash";
    }
    
    protected function _curl($uri,$data){
        $url = $this->_url($uri,$data);
        $ch = curl_init($url);
        if(!empty($data)){
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($ch,CURLOPT_HEADER,false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        return curl_exec($ch);
    }
    
    protected function _call($uri,$data=''){
        $data = $this->_curl($uri,$data);
        $json=json_decode($data,true);
        if(is_null($json) or !$data){
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $err= ' - No errors';
                break;
                case JSON_ERROR_DEPTH:
                    $err= ' - Maximum stack depth exceeded';
                break;
                case JSON_ERROR_STATE_MISMATCH:
                    $err= ' - Underflow or the modes mismatch';
                break;
                case JSON_ERROR_CTRL_CHAR:
                    $err= ' - Unexpected control character found';
                break;
                case JSON_ERROR_SYNTAX:
                    $err= ' - Syntax error, malformed JSON';
                break;
                case JSON_ERROR_UTF8:
                    $err= ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
                default:
                    $err= ' - Unknown error';
                break;
            }
            throw new APIException('API error: '.$err.' '.$data);
        }
        return $json;
    }
    
    protected function _download($uri,$data=''){
        $data = $this->_curl($uri,$data);
        if(!$data){
            throw new APIException('API download error '.$data);
        }
        return $data;
    }
    
    public function __construct($endpoint,$key,$secret){
        $this->_endpoint=$endpoint;
        $this->_key=$key;
        $this->_secret=$secret;
    }
    
    /**
     *Runs a bulk query
     *
     *@return an array with three keys, status, batch and warning, the status will either be OK or ERR, the batch will be the batch ID used in subsequent calls and warnings will be any warnings i.e. the account is low on credits
     *@param array $msisdns an array of msisdns to check
     *@param string $type either basic or full
     *@param int $cachepersonaldays the number of days to check the personal cache
     *@param int $cacheshareddays the number of days to check the shared cache
     *@param int $cachesharedsave 1 if to save results to the shared cache, 0 to keep results private
     *@param bool $process if to process the batch immediately
     */
    
    public function bulk($msisdns,$type,$cachepersonaldays,$cacheshareddays,$cachesharedsave=1,$process=true){
        $data='';
        foreach($msisdns as $msisdn){
            $data.='msisdn[]='.$msisdn.'&';
        }
        
        $data.="type=$type&cachepersonaldays=$cachepersonaldays&cacheshareddays=$cacheshareddays&cachesharedsave=$cachesharedsave";
        if($process){
            $data.="&process=$process";
        }
        $res=$this->_call("/api/bulk",$data);
        return $res;
    }
    
    /**
     *Downloads the results of a batch in CSV format
     *
     *@return a CSV string containing the batch results
     *@param string $id the batch ID to download
     */
    
    public function csv_download($id) {
        return $this->_download('/api/bulk/csv/download/'.$id);
    }
    
    /**
     *Processes a batch that is in status 'holding'
     *
     *@return a string either OK or FAILED
     *@param string $id the batch ID to download
     */
    
    public function process($id) {
        return $this->_call('/api/bulk/process/'.$id);
    }
    
    public function check($msisdns,$type,$cachepersonaldays,$cacheshareddays,$cachesharedsave=1,$url=''){
        $data='';
        foreach($msisdns as $msisdn){
            $data.='msisdn[]='.$msisdn.'&';
        }
        
        if(!empty($url)){
            $data.='url='.urlencode($url).'&';
        }
        
        $data.="type=$type&cachepersonaldays=$cachepersonaldays&cacheshareddays=$cacheshareddays&cachesharedsave=$cachesharedsave";
        return $this->_call('/api/check',$data);
    }
    
    /**
     *Returns the status of a batch
     *
     *@return an array with three keys, 'remaining' the number of msisdns to process, 'csv' either a timestamp or false if there is a csv file generated, 'status' the status of the batch
     *@param string $id the batch ID to download
     */
    
    public function status($id){
        return $this->_call('/api/bulk/status/'.$id);
    }
    
    /**
     *Pauses a currently running batch
     *
     *@return a string either OK or FAILED
     *@param string $id the batch ID to download
     */
    
    public function pause($id){
        return $this->_call('/api/bulk/pause/'.$id,'dummy=1');
    }
    
    /**
     *Generates a csv for a 'holding' batch
     *
     *@return a string either OK or FAILED
     *@param string $id the batch ID to generate
     */
    
    public function generate($id){
        return $this->_call('/api/bulk/csv/generate/'.$id,'dummy=1');
    }
    
    /**
     *Deletes a batch
     *
     *@return a string either OK or FAILED
     *@param string $id the batch ID to delete
     */
    
    public function delete($id){
        return $this->_call('/api/bulk/delete/'.$id,'dummy=1');
    }
    
    /**
     *Return the number of credits on the account
     *
     *@return an array with keys fullcredits and basiccredits
     */
    
    public function balance(){
        return $this->_call('/api/balance');
    }
}

class APIException extends \Exception {
    
}
