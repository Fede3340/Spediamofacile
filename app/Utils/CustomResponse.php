<?php 

namespace App\Utils;

class CustomResponse {

    public static function setSuccessResponse($message, $code, $objName = null, $data = null) {

        $params = array(
            'success' => true,
            'status_code' => $code,
            'message' => $message,
        );

        if ($objName) {
            $params[$objName] = $data;
        }

        if ($objName === null && $data) {
            $params['data'] = $data;
        }

        return response()->json($params, $code);
    }



    public static function setFailResponse($message, $code, $errors = null) {

        $params = array(
            'success' => false,
            'message' => $message,
            'code' => $code,
            'errors' => $errors
        );

        return response()->json($params, $code);
    }
}