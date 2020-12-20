<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Wallet;
use App\Payment;
use SoapClient;
use App\Mail\CodeMailVerification;

class SoapController extends Controller
{
    public function client(){
        if($_SERVER['REQUEST_METHOD'] == 'GET'){
            if (isset($_GET['wsdl'])) {
                $autodiscover = new \Laminas\Soap\AutoDiscover();
                $autodiscover
                    ->setClass(ClientSp::class)
                    ->setUri('http://pasarela-soap.test/client');
                    // Emit the XML:
                header('Content-type: application/xml');
                return $autodiscover->toXml();
            }
        }

        $soap = new \SoapServer("http://pasarela-soap.test/client?wsdl");
        $soap->setClass(ClientSp::class);
        $soap->handle();
    }

    public function wallet(){
        if($_SERVER['REQUEST_METHOD'] == 'GET'){
            if (isset($_GET['wsdl'])) {
                $autodiscover = new \Laminas\Soap\AutoDiscover();
                $autodiscover
                    ->setClass(WalletSp::class)
                    ->setUri('http://pasarela-soap.test/wallet');
                    // Emit the XML:
                header('Content-type: application/xml');
                return $autodiscover->toXml();
            }
        }

        $soap = new \SoapServer("http://pasarela-soap.test/wallet?wsdl");
        $soap->setClass(WalletSp::class);
        $soap->handle();
    }

    public function payment(){
        if($_SERVER['REQUEST_METHOD'] == 'GET'){
            if (isset($_GET['wsdl'])) {
                $autodiscover = new \Laminas\Soap\AutoDiscover();
                $autodiscover
                    ->setClass(PaymentSp::class)
                    ->setUri('http://pasarela-soap.test/payment');
                    // Emit the XML:
                header('Content-type: application/xml');
                return $autodiscover->toXml();
            }
        }

        $soap = new \SoapServer("http://pasarela-soap.test/payment?wsdl");
        $soap->setClass(PaymentSp::class);
        $soap->handle();
    }
}

class ClientSp {
    /**
     * Este metodo retorna un string
     *
     * @param string $nombres
     * @param string  $cedula
     * @param string  $celular
     * @param string  $email
     * @return string
     */
    public function create($nombres, $cedula, $celular, $email)
    {
        //si falta algun campo retorna un mensaje de error
        if(!$nombres || !$cedula || !$celular || !$email){
            return json_encode(["error"=>true, "message"=>"Faltan algunos parametros para poder crear el cliente."]);
        }

        //si el cliente existe, retorna un mensaje con un error
        if(User::where('email',$email)->exists()){

            return json_encode(["error"=>true, "message"=>"Ya existe un cliente con el correo ingresado."]);

        }else{

            //crear el cliente si los no falta ningun campo y el cliente no existe
            $user = new User();
            $user->email = $email;
            $user->nombres = $nombres;
            $user->cedula = $cedula;
            $user->telefono = $celular;
    
            $user->save();

            //obtener el cliente que insertamos para saber su id
            $user = User::where('email',$email)->first();

            //crear la wallet del cliente nuevo
            $wallet = new Wallet();
            $wallet->user_id = $user->id;
            $wallet->saldo = 0;
            $wallet->save();

            if($user->save()){
                return json_encode(["error"=>false, "message"=>"Se ha creado el cliente correctamente"]);
            }
        }
    }

    public function index()
    {
        return User::get();
    }
}

class WalletSp {
    
    /**
     * Este metodo retorna un string
     *
     * @param string $cedula
     * @param string  $celular
     * @param float  $monto
     * @return string
     */
    public function recharge($cedula,$celular,$monto)
    {
        if(!$cedula || !$celular || !$monto){
            return json_encode(["error"=>true, "message"=>"faltan parametros para poder realizar la recarga."]);
        }

        if($monto <= 0){
            return json_encode(["error"=>true, "message"=>"el monto a recargar debe ser mayor a 0."]);
        }

        $user = User::where('cedula',$cedula)->where('telefono',$celular)->with('wallet')->get();
        if($user->isEmpty()){

            return json_encode(["error"=>true, "message"=>"No existe un cliente con ese documento ni el celular"]);

        }else{
            $wallet = $user[0]->wallet;

            $wallet->saldo += $monto;
    
            $wallet->save();

            if($wallet->save()){
                return json_encode(["error"=>false, "message"=>"Se ha recargado el saldo correctamente"]);
            }
        }
    }

    /**
     * Este metodo retorna un string
     *
     * @param string $cedula
     * @param string  $celular
     * @return string
     */
    public function search($cedula,$celular)
    {
        if(!$cedula && !$celular){
            return json_encode(["error"=>true, "message"=>"faltan parametros para poder realizar la consulta."]);
        }

        $client = User::where('cedula',$cedula)->where('telefono',$celular)->with('wallet')->get();

        if($client->isEmpty()){
            return json_encode(["error"=>true, "message"=>"No se encontro el cliente con los datos indicados."]);
        }

        return json_encode(["error"=>false, "data"=>$client]);

    }
}

class PaymentSp {
    /**
     * Este metodo retorna un string
     *
     * @param string $cedula
     * @param string  $email
     * @param string  $descripcion
     * @param float  $monto
     * @return string
     */
    public function pay($cedula,$email,$descripcion,$monto)
    {
        if(!$cedula || !$email || !$monto || !$descripcion){
            return json_encode(["error"=>true, "message"=>"faltan parametros para poder realizar el pago."]);
        }

        if($monto <= 0){
            return json_encode(["error"=>true, "message"=>"el monto a pagar debe ser mayor a 0."]);
        }

        $user = User::where('cedula',$cedula)->where('email',$email)->with('wallet')->get();
        if($user->isEmpty()){

            return json_encode(["error"=>true, "message"=>"No existe un cliente con ese documento ni el celular"]);

        }else{

            $wallet = $user[0]->wallet;
            if($monto > $wallet->saldo){
                return json_encode(["error"=>true, "message"=>"El monto a pagar es mayor al monto disponible en la wallet"]);
            }

            $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $permitted_numbers = '0123456789';


            $payment = new Payment();
            $payment->user_id = $user[0]->id;
            $payment->wallet_id = $wallet->id;
            $payment->monto = $monto;
            $payment->descripcion = $descripcion;
            $payment->session_id = $this->generate_string($permitted_chars,15);
            $payment->token = $this->generate_string($permitted_numbers,6);
            $payment->pagado = false;
            $payment->save();

            $payment = Payment::where('user_id',$user[0]->id)->where('pagado',false)->get();
            if(!$payment->isEmpty()){
                \Mail::to($user[0]->email)->send(new CodeMailVerification($payment[0]));
                return json_encode(["error"=>false, "message"=>"Se ha enviado el correo de verificacion"]);
                
            }else{
                return json_encode(["error"=>true, "message"=>"No existe el pago"]);
            }

        }
    }

    /**
     * Este metodo retorna un string
     *
     * @param string $token
     * @param string  $session_id
     * @return string
     */
    public function confirmPayment($token,$session_id)
    {
        if(!$token && !$session_id){
            return json_encode(["error"=>true, "message"=>"faltan parametros para poder confirmar el pago."]);
        }

        $payment = Payment::where('token',$token)->where('session_id',$session_id)->with('wallet')->get();

        if($payment->isEmpty()){
            
            return json_encode(["error"=>true, "message"=>"Token o identificacion de sesion incorrectos."]);

        }else{
            $wallet = $payment[0]->wallet;

            if($payment[0]->monto > $wallet->saldo){
                return json_encode(["error"=>true, "message"=>"El monto a pagar es mayor al monto disponible en la wallet"]);
            }

            $wallet->saldo -= $payment[0]->monto;
            $wallet->save();

            $payment[0]->pagado = true;
            $payment[0]->save();

            if($payment[0]->save()){
                return json_encode(["error"=>false, "message"=>"Se ha realizado el pago correctamente."]);
            }else{
                return json_encode(["error"=>true, "message"=>"Error al realizar el pago."]); 
            }
        }

    }

    public function generate_string($input, $strength = 16) {
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
     
        return $random_string;
    }
}

