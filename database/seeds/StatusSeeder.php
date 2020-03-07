<?php

use Illuminate\Database\Seeder;
use App\Status;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $status = new Status();
        $status->id = 1;
        $status->name = "Solicitar Nombre";
        $status->save();

        $status = new Status();
        $status->id = 2;
        $status->name = "Solicitar Pedido";
        $status->save();

        $status = new Status();
        $status->id = 3;
        $status->name = "Solicitar Ubicación";
        $status->save();

        $status = new Status();
        $status->id = 4;
        $status->name = "Solicitar Pago";
        $status->save();

        $status = new Status();
        $status->id = 5;
        $status->name = "Terminado";
        $status->save();

        $status = new Status();
        $status->id = 6;
        $status->name = "Cancelado";
        $status->save();

    }
}
