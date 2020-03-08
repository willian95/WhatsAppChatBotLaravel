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
                
                if($previousOrder->status_id == 1){
                    $reponse = $this->update($request->phone, $request->name);
                    return response()->json($reponse);
                }

                if($previousOrder == null && $customer->name != ""){
                    $menu = $this->menu();
                    return response()->json(["success" => true, "menu" => $menu]);
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

                return response()->json(["success" => true, "statusOrder" => $order->id]);

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

            $menu = $this->menu;

            return ["success" => true, "statusId" => $previousOrder->status_id, "menu" => $menu];

        }catch(\Exception $e){

            return ["success" => false, "msg" => "Error en el servidor", "error" => $e->getMessage(), "line" => $e->getLine()];

        }

    }

    public function menu(){

        $menu = Menu::all();
        return $menu;

    }

}
