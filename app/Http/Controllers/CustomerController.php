<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CustomerStoreRequest;
use App\Customer;
use App\Order;
use App\Menu;

class CustomerController extends Controller
{

    public function checkCustomer(Request $request){

        try{

            $previousOrder = null;
            $customer = Customer::where("phone", $request->phone)->first();
            
            if($customer){

                $previousOrder = Order::where('customer_id', $customer->id)->where('status_id', '<', "5")->orderBy('id', 'desc')->first();
                
                if($previousOrder == null && $customer->name != ""){
                    
                    $order = new Order();
                    $order->customer_id = $customer->id;
                    $order->status_id = 1;
                    $order->save();

                    $menu = $this->menu();
                    $menuString = "";

                    foreach($menu as $m){
                        $menuString .= $m->id."-".$m->name."\n".$m->description."\n\n";
                    }

                    return response()->json(["success" => true, "statusOrder" => $order->status_id, "msg" => "Hola de nuevo ".$customer->name.". Tenemos estas opciones para ti: \n".$menuString."\n\n"."Para realizar su pedido debe hacerlo de la siguiente forma: número de opción-cantidad, número de opción - cantidad,..."]);
                }

                if($previousOrder->status_id == 1){
                    $reponse = $this->update($request->phone, $request->body);
                    return response()->json($reponse);
                }

                if($previousOrder->status_id == 2){
                    
                    $response = $this->takeOrder($request->phone, $request->body);

                    if($response["success"] == true){

                        $customer = Customer::where('phone', $request->phone)->first();

                        $previousOrder = Order::where('customer_id', $customer->id)->where('status_id', '<', "5")->orderBy('id', 'desc')->first();
                        $previousOrder->status_id = 3;
                        $previousOrder->update();

                        return response()->json(["success" => true, "statusOrder" => $previousOrder->status_id, "msg" => "Ya tenemos tu orden"]);
                    }
                    else if(strpos($reponse["success"], "no available") > -1){

                        $item_id = substr($reponse["success"], strpos($reponse["success"], "-"), strlen($response["success"]));
                        return response()->json($item_id);
                        //return response()->json(["success" => true, "statusOrder" => 2, "msg" => "La opción ".$item_id." no existe, vuelva a verificar"]);

                    }
                    else{

                        $menu = $this->menu();
                        $menuString = "";

                        foreach($menu as $m){
                            $menuString .= $m->id."-".$m->name."\n".$m->description."\n\n";
                        }

                        return response()->json(["success" => true, "statusOrder" => 2, "msg" => "Tenemos un problema con su orden, no está bien realizada. Recuerde que debe ser de la siguiente forma: número-cantidad,número-cantidad,... \n\n".$menuString]);
                    }

                }

                return response()->json(["success" => true, "statusOrder" => $previousOrder->status_id]);
            
            }else{

                $customer = new Customer();
                $customer->phone = $request->phone;
                $customer->save();

                $order = new Order();
                $order->customer_id = $customer->id;
                $order->status_id = 1;
                $order->save();

                return response()->json(["success" => true, "statusOrder" => $order->status_id, "msg" => "Aún no nos conocemos. ¿Cuál es tu nombre?"]);

            }

        }catch(\Exception $e){

            return response()->json(["success" => false, "msg" => "Error en el servidor", "error" => $e->getMessage(), "line" => $e->getLine()]);

        }

    }

    public function update($phone, $name){

        try{

            $customer = Customer::where('phone', $phone)->first();
            $customer->name = $name;
            $customer->update();

            $previousOrder = Order::where('customer_id', $customer->id)->where('status_id', '<', "5")->orderBy('id', 'desc')->first();
            $previousOrder->status_id = 2;
            $previousOrder->update();

            $menu = $this->menu();
            $menuString = "";

            foreach($menu as $m){
                $menuString .= $m->id."-".$m->name."\n".$m->description."\n\n";
            }

            return ["success" => true, "statusOrder" => $previousOrder->status_id, "msg" => "¿Que tal ".$customer->name."? Tenemos estas opciones para ti: \n".$menuString."\n\n"."Para realizar su pedido debe hacerlo de la siguiente forma: número de opción-cantidad, número de opción - cantidad,..."];

        }catch(\Exception $e){

            return ["success" => false, "msg" => "Error en el servidor", "error" => $e->getMessage(), "line" => $e->getLine()];

        }

    }

    public function menu(){

        $menu = Menu::all();
        return $menu;

    }

    public function checkOrder($order){

        $flag = true;
        $order = str_replace(' ', '', $order);

        for($i=0;$i<strlen($order);$i++){

            if(is_numeric($order[$i]) || $order[$i] == "," || $order[$i] == "-"){

            }else{
                $flag = false;
                break;
            }

        }

        return $flag;

    }

    public function takeOrder($phone, $order){

        try{

            if($this->checkOrder($order)){

                $flag = true;
                $noAvailableId = 0;

                $order = str_replace(' ', '', $order);
                $orderItems = explode(',', $order);

                foreach($orderItems as $item){

                    $itemParts = explode('-', $item);   
                    $item_id = $itemParts[0];
                    $item_amount = $itemParts[1]; 
                    
                    $isAvailable = Menu::where('id', $item_id)->first();
                    if($isAvailable == null){
                        $flag = false;
                        $noAvailableId = $item_id;
                        break;
                    }

                }

                if($flag == false){
                    return ["success" => "no available-".$noAvailableId];
                }
                else{

                    return ["success" => true];   
                }

            }else{
                return ["success" => "warn"];
            }

        }catch(\Exception $e){
            return ["success" => false, "msg" => "Error en el servidor", "error" => $e->getMessage(), "line" => $e->getLine()];
        }

    }

}
