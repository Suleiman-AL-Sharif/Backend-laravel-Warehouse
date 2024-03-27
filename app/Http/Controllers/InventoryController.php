<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Import;
use App\Models\Export;
use App\Models\Lost;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\lowStockNotification;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Nette\Utils\Strings;
use PhpParser\Node\Expr\List_;

class InventoryController extends Controller
{

    public function add_product(Request $request)
    {


        /* try{ $request->validate([

             "products.*.product"=>"required|string",
             "products.*.siz"=>"required|integer|min:0"
           ]);
          }
          catch(Exception $exc)
          {
            return $exc->getMessage();
          }
           $products = $request->products;
           foreach ($request->input($products,[] )as $product) {
             $inventory = new Inventory();
             $inventory->user_id= auth()->user()->id;
             $inventory->product =  $product['product'];
             $inventory->siz =  $product['siz'];
             $inventory->save();
           }

           return response()->json([
            "status"=>200,
            "message"=>"products created successfully ",

           ]);*/

        $request->validate([
            "product" => "required",
            "type" => "required"
        ]);

        $inventory = new Inventory();
        $inventory->user_id = auth()->user()->id;
        $inventory->product =  $request->product;
        if (isset($request->siz)) {
            $inventory->siz =  $request->siz;
        }
        $inventory->colore =  $request->colore;
        $type =  $request->type;
        $category = Category::where('type', $type)->first();
        $category_id = $category['id'];
        $inventory->category_id =  $category_id;

        $inventory->save();

        return response()->json([
            "message" => "product created successfully ",

        ], 200);
    }

    public function edit_siz(Request $request)
    {
        try {
            $request->validate([
                "product" => "required|string",
                "new_siz" => "required|integer"
            ]);
        } catch (Exception $exc) {
            return $exc->getMessage();
        }

        $name = $request->product;
        $siz =  $request->new_siz;

        $product = inventory::where([
            'product' => $name,
            'user_id' => auth()->user()->id
        ])->first();

        if ($product == null) {
            return response()->json([
                "message" => "product was not found",

            ]);
        }
        $amount =  $product['amount'];
        if ($siz >=  $amount) {
            $product->siz = $siz;
            $product->save();
            return response()->json([
                "message" => "siz has been edited successfully"
            ], 200);
        } else {
            return response()->json([
                "message" => "cant edit siz inter bigger siz"
            ], 200);
        }
    }



    public function naw(Request $request, $date)
    {

        if ($date == "Recent Quantity") {
            $naw = Inventory::where('user_id', auth()->user()->id)
                ->select('product', 'amount', 'siz')->get();
            if ($naw->isEmpty()) {
                return response()->json([
                    "message" => "no products in inventory",

                ], 201);
            }
            return response()->json([
                "products naw" => $naw,

            ], 200);
        } elseif ($date == "Import") {

            $datestr1 =  $request->date1;
            $datestr2 =  $request->date2;



            if ($datestr2 == null &&  $datestr1 != null) {

                $date1 =  Carbon::parse($datestr1)->format('Y-m-d');
                $product = Import::where([
                    'user_id' => auth()->user()->id,
                    'date' => $date1
                ])->get();
            } elseif ($datestr1 == null  &&  $datestr2 != null) {
                $date2 =  Carbon::parse($datestr2)->format('Y-m-d');
                $product = Import::where([
                    'user_id' => auth()->user()->id,
                    'date' => $date2
                ])->get();
            } elseif ($datestr1 == null  &&  $datestr2 == null) {
                return response()->json([
                    "message" => "please inter date ",
                ], 201);
            } else {
                $date1 =  Carbon::parse($datestr1)->format('Y-m-d');
                $date2 =  Carbon::parse($datestr2)->format('Y-m-d');

                $product = Import::where('user_id', auth()->user()->id,)
                    ->whereBetween('date', [$date1, $date2])->get();
            }

            if ($product->isEmpty()) {
                return response()->json([

                    "message" => "no products were imported during this date ",
                ], 202);
            }

            return response()->json([

                "import in this date" => $product,

            ], 200);
        } elseif ($date == "Export") {


            $datestr1 =  $request->date1;
            $datestr2 =  $request->date2;



            if ($datestr2 == null &&  $datestr1 != null) {

                $date1 =  Carbon::parse($datestr1)->format('Y-m-d');
                $product = Export::where([
                    'user_id' => auth()->user()->id,
                    'date' => $date1
                ])->get();
            } elseif ($datestr1 == null  &&  $datestr2 != null) {
                $date2 =  Carbon::parse($datestr2)->format('Y-m-d');
                $product = Export::where([
                    'user_id' => auth()->user()->id,
                    'date' => $date2
                ])->get();
            } elseif ($datestr1 == null  &&  $datestr2 == null) {
                return response()->json([
                    "status" => 200,
                    "message" => "please inter date ",
                ]);
            } else {
                $date1 =  Carbon::parse($datestr1)->format('Y-m-d');
                $date2 =  Carbon::parse($datestr2)->format('Y-m-d');

                $product = Export::where('user_id', auth()->user()->id,)
                    ->whereBetween('date', [$date1, $date2])->get();
            }

            if ($product->isEmpty()) {
                return response()->json([
                    "status" => 200,
                    "message" => "no products were exported during this date ",
                ]);
            }

            return response()->json([
                "status" => 200,
                "export in this date" => $product,

            ]);
        } elseif ($date == "Corrupted") {


            $datestr1 =  $request->date1;
            $datestr2 =  $request->date2;



            if ($datestr2 == null &&  $datestr1 != null) {

                $date1 =  Carbon::parse($datestr1)->format('Y-m-d');
                $product = Lost::where([
                    'user_id' => auth()->user()->id,
                    'date' => $date1
                ])->get();
            } elseif ($datestr1 == null  &&  $datestr2 != null) {
                $date2 =  Carbon::parse($datestr2)->format('Y-m-d');
                $product = Lost::where([
                    'user_id' => auth()->user()->id,
                    'date' => $date2
                ])->get();
            } elseif ($datestr1 == null  &&  $datestr2 == null) {
                return response()->json([
                    "status" => 200,
                    "message" => "please inter date ",
                ]);
            } else {
                $date1 =  Carbon::parse($datestr1)->format('Y-m-d');
                $date2 =  Carbon::parse($datestr2)->format('Y-m-d');

                $product = Lost::where('user_id', auth()->user()->id,)
                    ->whereBetween('date', [$date1, $date2])->get();
            }

            if ($product->isEmpty()) {
                return response()->json([
                    "status" => 200,
                    "message" => "no products were losted during this date ",
                ]);
            }

            return response()->json([
                "status" => 200,
                "lost in this date" => $product,

            ]);
        }
    }

    public function product(Request $request)
    {

        $product = $request->product;

        $details = Inventory::where([
            'user_id' => auth()->user()->id,
            'product' =>  $product
        ])->first();

        return response()->json([
            "product" =>  $details
        ], 200);
    }


    public function lostIn(Request $request)
    {
        $user = auth()->user();
        try {
            $request->validate([
                "losts" => "required|array",
                "losts.*.product" => "required",
                "losts.*.lost" => "required|integer",
                "date" => "required"
            ]);
        } catch (Exception $exc) {
            return $exc->getMessage();
        }
        $myList = collect();

        $datestr =  $request->date;
        $date = Carbon::createFromFormat('m/d/Y', $datestr)->format('Y/m/d');
        $cause =  $request->cause;
        $losts = $request->losts;
        foreach ($losts as $losts) {

            $product =  $losts['product'];
            $lostn =  $losts['lost'];
            $inventory = Inventory::where([
                'user_id' => auth()->user()->id,
                'product' => $product
            ])->first();
            if ($inventory == null) {
                return response()->json([

                    "message" => "this product was not found ",

                ], 202);
            }
            $amount =  $inventory['amount'];


            if ($lostn <=  $amount) {

                $lost = new Lost();

                $lost->user_id = auth()->user()->id;
                $lost->product = $product;
                $lost->amount = $lostn;
                $lost->date =  $date;
                $lost->cause =  $cause;
                $lost->save();

                $inventory->update(['amount' => $amount -  $lostn]);
                $inventory->save();
                if ($inventory->amount < 200) {
                    Notification::send($user, new lowStockNotification($inventory->product));
                }
            }
        }
    }


    public function import(Request $request)
    {

        try {
            $request->validate([
                "imports" => "required|array",
                "imports.*.product" => "required",
                "imports.*.import" => "required|integer",
                "date" => "required"
            ]);
        } catch (Exception $exc) {
            return $exc->getMessage();
        }

        $myList = collect();
        $datestr =  $request->date;

        //  $date = Carbon::createFromFormat('Y-m-s H:i:s', $datestr)->format('Y/m/d');
        $date =  Carbon::parse($datestr)->format('Y-m-d');
        $imports = $request->imports;

        foreach ($imports as $imports) {


            $product =  $imports['product'];
            $importn =  $imports['import'];



            $inventory = Inventory::where([
                'user_id' => auth()->user()->id,
                'product' => $product
            ])->first();

            if ($inventory == null) {
                return response()->json([
                    "message" => "this product was not found ",

                ], 201);
            }
            // $inventory = $inventory[0];
            $amount =  $inventory['amount'];
            $siz =  $inventory['siz'];

            if ($importn <= $siz - $amount) {

                $import = new Import();

                $import->user_id = auth()->user()->id;
                $import->product = $product;
                $import->amount = $importn;
                $import->amountOld = $importn;
                $import->date =  $date;
                $import->save();

                $inventory->update(['amount' => $amount + $importn]);
                $inventory->save();
            } else {
                $myObject = (object) [
                    'name' => $product,
                    'you can import only' => $siz - $amount,
                ];

                $myList->push($myObject);
            }
        }
        return response()->json([
            "import done without" => $myList
        ], 200);
    }


    public function export(Request $request, $method)
    {
        $user = auth()->user();
        try {
            $request->validate([
                "exports" => "required|array",
                "exports.*.product" => "required",
                "exports.*.export" => "required|integer",
                "date" => "required"
            ]);
        } catch (Exception $exc) {
            return $exc->getMessage();
        }

        $myList = collect();

        $datestr =  $request->date;
        $date = Carbon::createFromFormat('m/d/Y', $datestr)->format('Y/m/d');
        $exports = $request->exports;
        foreach ($exports as $exports) {

            $product =  $exports['product'];
            $exportn =  $exports['export'];


            $inventory = Inventory::where([
                'user_id' => auth()->user()->id,
                'product' => $product
            ])->first();
            if ($inventory == null) {
                return response()->json([
                    "status" => 200,
                    "message" => "this product was not found ",

                ], 201);
            }
            $amount =  $inventory['amount'];


            if ($exportn <=  $amount) {

                $export = new export();

                $export->user_id = auth()->user()->id;
                $export->product = $product;
                $export->amount = $exportn;
                $export->date =  $date;
                $export->save();

                $inventory->update(['amount' => $amount -  $exportn]);
                $inventory->save();

                if ($inventory->amount < 200) {
                    Notification::send($user, new lowStockNotification($inventory->product));
                }

                while ($exportn != 0) {

                    if ($method == "old") {
                        $old = Import::where([
                            'user_id' => auth()->user()->id,
                            'product' => $product
                        ])->where('amountOld', '!=', 0)->orderBy('date', 'asc')->first();
                    } elseif ($method == "new") {
                        $old = Import::where([
                            'user_id' => auth()->user()->id,
                            'product' => $product
                        ])->where('amountOld', '!=', 0)->orderBy('date', 'desc')->first();
                    } elseif ($method == "random") {
                        $old = Import::where([
                            'user_id' => auth()->user()->id,
                            'product' => $product
                        ])->where('amountOld', '!=', 0)->inRandomOrder()->first();
                    }
                    $amountOld =  $old['amountOld'];

                    if ($amountOld >= $exportn) {

                        $old->update(['amountOld' => $amountOld -  $exportn]);
                        $old->save();
                        $exportn = 0;
                    } else {

                        $old->update(['amountOld' => 0]);
                        $old->save();
                        $exportn = $exportn - $amountOld;
                    }
                }
            } else {
                $myObject = (object) [
                    'name' => $product,
                    'you can export only' =>  $amount,
                ];

                $myList->push($myObject);
            }
        }

        return response()->json([
            "export done without" => $myList
        ], 200);
    }


    public function inventory(Request $request,)
    {
        try {
            $request->validate([
                "inventory" => "required|integer"
            ]);
        } catch (Exception $exc) {
            $exc->getMessage();
        }
        $product =  $request->product;
        $env =  $request->inventory;

        $inventory = Inventory::where([
            'user_id' => auth()->user()->id,
            'product' => $product
        ])->first();
        if ($inventory == null) {
            return response()->json([

                "message" => "prduct are not found "
            ], 203);
        }
        $amount =  $inventory['amount'];

        if ($amount == $env) {
            $n1 =  "there is no differencess";
            $mergedString = implode(" : ", [$product, $n1]);
            return response()->json([
                "messag" =>  $mergedString
            ], 200);
        } elseif ($amount < $env) {

            $message = "you have an increase";
            $n = $env - $amount;
            $n1 = strval($n);
            $mergedString = implode(" : ", [$product, $message, $n1]);


            return response()->json([
                "message" => $mergedString,
            ], 201);
        } else {
            $message = "you have an decrease";
            $n =  $amount - $env;
            $n1 = strval($n);
            $mergedString = implode(" : ", [$product, $message, $n1]);

            return response()->json([
                "message" => $mergedString,
            ], 202);
        }
    }

    public function cost(Request $request)
    {

        $product = $request->product;
        $price = $request->price;


        $inventory = Inventory::where([
            'user_id' => auth()->user()->id,
            'product' => $product
        ])->first();

        $amount =  $inventory['amount'];
        $cost =  $amount * $price;

        $message = "the value is";
        $n1 = strval($cost);
        $mergedString = implode(" : ", [$product, $message, $n1]);


        return response()->json([
            "message" => $mergedString,
        ], 201);
    }

    public function product_count()
    {

        $count = Inventory::where('user_id', auth()->user()->id,)->count();

        return response()->json([
            "num_products" =>  $count
        ], 200);
    }


    public function product_name()
    {

        $name = Inventory::where('user_id', auth()->user()->id)->pluck('product')->toArray();

        return response()->json([
            "name_products" =>  $name
        ], 200);
    }

    public function add_employee(Request $request)
    {


        try {
            $request->validate([
                "name" => "required",
                "email" => "required",
                "phone_n" => "required"
            ]);
        } catch (Exception $exc) {
            return $exc->getMessage();
        }

        $employee = new Employee();

        $employee->user_id = auth()->user()->id;
        $employee->name = $request->name;
        $employee->email = $request->email;
        $employee->phone_n = $request->phone_n;

        $employee->save();

        return response()->json([
            "status" => 200,
            "messag" => "employee added successfully"
        ], 200);
    }


    public function category()
    {
        $Category = Category::select('type')->get();

        return response()->json([
            "category" =>  $Category
        ], 200);
    }


    public function export1(Request $request, $method)
    {
        $user = auth()->user();
        try {
            $request->validate([

                "product" => "required",
                "export" => "required|integer",
                "date" => "required"
            ]);
        } catch (Exception $exc) {
            return $exc->getMessage();
        }



        $datestr =  $request->date;
        $date =  Carbon::parse($datestr)->format('Y-m-d');

        $product = $request->product;
        $exportn = $request->export;


        $inventory = Inventory::where([
            'user_id' => auth()->user()->id,
            'product' => $product
        ])->first();
        if ($inventory == null) {
            return response()->json([
                "status" => 200,
                "message" => "this product was not found ",

            ], 201);
        }
        $amount =  $inventory['amount'];


        if ($exportn <=  $amount) {

            $export = new export();

            $export->user_id = auth()->user()->id;
            $export->product = $product;
            $export->amount = $exportn;
            $export->date =  $date;
            $export->save();

            $inventory->update(['amount' => $amount -  $exportn]);
            $inventory->save();

            if ($inventory->amount < 200) {
                Notification::send($user, new lowStockNotification($inventory->product));
            }

            while ($exportn != 0) {

                if ($method == "old") {
                    $old = Import::where([
                        'user_id' => auth()->user()->id,
                        'product' => $product
                    ])->where('amountOld', '!=', 0)->orderBy('date', 'asc')->first();
                } elseif ($method == "new") {
                    $old = Import::where([
                        'user_id' => auth()->user()->id,
                        'product' => $product
                    ])->where('amountOld', '!=', 0)->orderBy('date', 'desc')->first();
                } elseif ($method == "random") {
                    $old = Import::where([
                        'user_id' => auth()->user()->id,
                        'product' => $product
                    ])->where('amountOld', '!=', 0)->inRandomOrder()->first();
                }
                $amountOld =  $old['amountOld'];

                if ($amountOld >= $exportn) {

                    $old->update(['amountOld' => $amountOld -  $exportn]);
                    $old->save();
                    $exportn = 0;
                } else {

                    $old->update(['amountOld' => 0]);
                    $old->save();
                    $exportn = $exportn - $amountOld;
                }
            }
            $n1 =  "export added successfully";
            $mergedString = implode(" : ", [$product, $n1]);
            return response()->json([
                "messag" =>  $mergedString
            ], 200);
        } else {
            $message = "you can export only";
            $n =  $amount;
            $n1 = strval($n);
            $mergedString = implode(" : ", [$product, $message, $n1]);


            return response()->json([
                "message" => $mergedString,
            ], 201);
        }
    }


    public function import1(Request $request)
    {

        try {
            $request->validate([
                "product" => "required",
                "import" => "required|integer",
                "date" => "required"
            ]);
        } catch (Exception $exc) {
            return $exc->getMessage();
        }


        $datestr =  $request->date;

        //  $date = Carbon::createFromFormat('Y-m-s H:i:s', $datestr)->format('Y/m/d');
        $date =  Carbon::parse($datestr)->format('Y-m-d');

        $product =  $request->product;
        $importn = $request->import;


        $inventory = Inventory::where([
            'user_id' => auth()->user()->id,
            'product' => $product
        ])->first();

        if ($inventory == null) {
            return response()->json([
                "message" => "this product was not found ",

            ]);
        }
        // $inventory = $inventory[0];
        $amount =  $inventory['amount'];
        $siz =  $inventory['siz'];

        if ($importn <= $siz - $amount) {

            $import = new Import();

            $import->user_id = auth()->user()->id;
            $import->product = $product;
            $import->amount = $importn;
            $import->amountOld = $importn;
            $import->date =  $date;
            $import->save();

            $inventory->update(['amount' => $amount + $importn]);
            $inventory->save();

            $n1 =  "import added successfully";
            $mergedString = implode(" : ", [$product, $n1]);
            return response()->json([
                "messag" =>  $mergedString
            ], 200);
        } else {
            $message = "you can import only";
            $n =  $siz - $amount;
            $n1 = strval($n);
            $mergedString = implode(" : ", [$product, $message, $n1]);


            return response()->json([
                "message" => $mergedString,
            ], 201);
        }
    }
}
