<?php
class auth{

    public function getAuth(){

        $ch = curl_init();
        $timestamp = new DateTime();
        
        curl_setopt($ch, CURLOPT_URL, 'https://api.klikbca.com/api/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        
        $headers = array();
        $headers[] = 'Content-Type:application/x-www-form-urlencoded';
        $headers[] = 'Authorization:Basic ZmU5ODI2ZjctYmRiMi00NjQ0LWI3NTctZGFmNWYwODkzYTFiOjI1NzQ2NDZiLTAyYTctNDliYS04NTg0LTg1MTg4ZWVjM2I1Yw==';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            
        }
        
        return $result;
        curl_close($ch);
    }
    
    public function getAuthDev(){

        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, 'https://devapi.klikbca.com/api/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        
        $headers = array();
        $headers[] = 'Content-Type:application/x-www-form-urlencoded';
        $headers[] = 'Authorization:Basic ZWMwZjE4ZWItM2E0NC00NDIwLWI1NjItNzFhMGI0MmFiZDgzOjA3NmI0MDMxLTRiNjctNDBlNy1hYjEwLTRhYjM1OWM3MWRiMw==';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }

        return $result;
        curl_close($ch);
    }
}
?>