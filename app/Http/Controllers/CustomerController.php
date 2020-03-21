<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
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

                $previousOrder = Order::where('customer_id', $customer->id)->where('status_id', '<', "6")->orderBy('id', 'desc')->first();
                
                if($previousOrder == null && $customer->name != ""){
                    
                    $order = new Order();
                    $order->customer_id = $customer->id;
                    $order->status_id = 2;
                    $order->save();

                    $menu = $this->menu();
                    $menuString = "";

                    foreach($menu as $m){
                        $menuString .= $m->id."-".$m->name."\n"."Precio: ".$m->price." $"."\n".$m->description."\n\n";
                    }

                    return response()->json(["success" => true, "statusOrder" => $order->status_id, "msg" => "Hola de nuevo ".$customer->name.". Tenemos estas opciones para ti: \n".$menuString."\n"."Para realizar su pedido debe hacerlo de la siguiente forma: número de opción-cantidad, número de opción - cantidad. Las 'comas' y 'guiones' son importantes. Por ejemplo: 1-2, 2-3, 3-1, ..."]);
                }

                if($previousOrder->status_id == 1){
                    $reponse = $this->update($request->phone, $request->body);
                    return response()->json($reponse);
                }

                if($previousOrder->status_id == 2){
                    
                    Log::info("status 2");

                    $response = $this->takeOrder($request->phone, $request->body);
                   
                    if(strpos($response["success"], "no available") > -1){

                        $item_id = substr($response["success"], strpos($response["success"], "-") + 1, strlen($response["success"]));
                        //return response()->json($item_id);

                        if(strpos($item_id, ",") > -1){
                            $message = "Las opciones ".$item_id." no existen, vuelva a verificar";
                        }else{
                            $message = "La opción ".$item_id." no existe, vuelva a verificar";
                        }

                        return response()->json(["success" => true, "statusOrder" => 2, "msg" => $message]);

                    }
                    else if($response["success"] == "true"){

                        $customer = Customer::where('phone', $request->phone)->first();

                        $previousOrder = Order::where('customer_id', $customer->id)->where('status_id', '<', "5")->orderBy('id', 'desc')->first();
                        $previousOrder->status_id = 3;
                        $previousOrder->update();

                        $message = "";
                        $orderExploded = explode(",", $previousOrder->order);
                        
                        foreach($orderExploded as $exploded){

                            $itemParts = explode('-', $exploded);   
                            $item_id = $itemParts[0];
                            $item_amount = $itemParts[1]; 
                            
                            $option = Menu::where('id', $item_id)->first();  
                            
                            $message .= $item_amount." ".$option->name." ".$option->price."$"."\n"; 

                        }

                        return response()->json(["success" => true, "statusOrder" => $previousOrder->status_id, "msg" => "Es esta su orden: "."\n".$message."\n"."Si es correcta marque 1, sino, marque 2"]);
                    }
                    else{

                        $menu = $this->menu();
                        $menuString = "";

                        foreach($menu as $m){
                            $menuString .= $m->id."-".$m->name."\n".$m->description."\n\n";
                        }

                        return response()->json(["success" => true, "statusOrder" => 2, "msg" => "Tenemos un problema con su orden, no está bien realizada. Recuerde que debe ser de la siguiente forma: número-cantidad,número-cantidad. Las 'comas' y 'guiones' son importantes. Por ejemplo: 1-2, 2-3, 3-1, ... \n".$menuString]);
                    
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
            Log::info("global error");
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

                Log::info("entre");

                $flag = true;
                $noAvailableId = 0;

                $order = str_replace(' ', '', $order);
                $orderItems = explode(',', $order);

                Log::info('order: '.$order);

                foreach($orderItems as $item){

                    $itemParts = explode('-', $item);   
                    $item_id = $itemParts[0];
                    $item_amount = $itemParts[1]; 
                    
                    $isAvailable = Menu::where('id', $item_id)->first();
                    if($isAvailable == null){
                        $flag = false;
                        if($noAvailableId == 0)
                            $noAvailableId = $item_id;
                        else
                            $noAvailableId .= ",".$item_id;
                    }

                    Log::info('Pay attention to this: '.$itemParts[0]);
                    log::info("info: ".$isAvailable);

                }

                if($flag == false){
                    return ["success" => "no available-".$noAvailableId];
                }
                else{

                    $customer = Customer::where('phone', $phone)->first();
                    $previousOrder = Order::where('customer_id', $customer->id)->where('status_id', '<', "5")->orderBy('id', 'desc')->first();
                    $previousOrder->order = str_replace(' ', '', $order);
                    $previousOrder->update();

                    return ["success" => "true"];   
                }

            }else{
                Log::info("no entro");
                return ["success" => "warn"];
            }

        }catch(\Exception $e){
            Log::info("error");
            return ["success" => false, "msg" => "Error en el servidor", "error" => $e->getMessage(), "line" => $e->getLine()];
        }

    }

}
