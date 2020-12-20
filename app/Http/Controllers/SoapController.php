<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Wallet;
use SoapClient;
use Spatie\ArrayToXml\ArrayToXml;

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
}

class ClientSp {
    
    public function create($nombres, $cedula, $celular, $email)
    {
        //si falta algun campo retorna un mensaje de error
        if(!$nombres || !$cedula || !$celular || !$email){
            return ["error"=>true, "message"=>"Faltan algunos parametros para poder crear el cliente."];
        }

        //si el cliente existe, retorna un mensaje con un error
        if(User::where('email',$email)->exists()){

            return ["error"=>true, "message"=>"Ya existe un cliente con el correo ingresado."];

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
                return ["error"=>false, "message"=>"Se ha creado el cliente correctamente"];
            }
        }
    }

    public function index()
    {
        return User::get();
    }
}

class WalletSp {
    
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

    public function search($cedula,$celular)
    {
        if(!$cedula && !$celular){
            return json_encode(["error"=>true, "message"=>"faltan parametros para poder realizar la consulta."]);
        }

        $client = User::where('cedula',$cedula)->where('telefono',$celular)->with('wallet')->get();

        if($client->isEmpty()){
            return json_encode(["error"=>true, "message"=>"No se encontro el cliente con los datos indicados."]);
        }

        return ["error"=>false, "data"=>$client];

    }
}