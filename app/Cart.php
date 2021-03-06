<?php

namespace App;

use Illuminate\Http\Request;
use Session;
use App\StoredItem;

class Cart{
    public $items = null;
    public $totalQty = 0;
    public $totalPrice = 0;

    /**
     * Look if a cart already exists if so get those values.
     * 
     * @param $oldCart = The old cart with the values.
     */
    public function __construct(){
        $oldCart = Session::has("cart") ? Session::get("cart") : null;
        if($oldCart){
            $this->items = $oldCart->items;
            $this->totalQty = $oldCart->totalQty;
            $this->totalPrice = $oldCart->totalPrice;
        }
    }

    /**
     * Add a item to the cart.
     * 
     * @param $storedItem = The item that is gonna be added.
     * @param $item = Item it self with all the info.
     * @param int $id = id of the selected item.
     * @param int $amount = Amount of the selected item. 
     */
    public function add(Request $request, $item, $id,$amount){
        $storedItem = new StoredItem($item);
        if($this->items){
            if(array_key_exists($id, $this->items)){
                $storedItem = $this->items[$id];
                $this->totalPrice -= $storedItem->price;
            }
        }
        $storedItem->qty += $amount;
        $storedItem->price = $item->product_price * $storedItem->qty;
        $this->items[$id] = $storedItem;
        $this->totalQty += $amount;
        // += = $this->totalPrice = $this->totalPrice + $item->price
        $this->totalPrice += $storedItem->price;
        $request->session()->put("cart", $this);
    }

    /**
     * Gets all the ids from the items within the cart.
     * 
     * @param $ids = Array with all the ids form the items that are in the cart.
     */
    public function getIdsInCart(){
        $ids = [];
        foreach($this->items as $item){
            array_push($ids, $item->item->id);
        }
        return $ids;
    }

    /**
     * Checks if the amount is still the same as before.
     * 
     * @param $ids = Array with all the ids form the items that are in the cart.
     * @param int $amounts = The amounts taken from the inputs.
     */
    public function checkAmountOfItems(Request $request, $ids, $amounts){
        foreach($ids as $id){
            if($this->items[$id]->qty != $amounts[$id]){
                $this->changeAmountOfItem($request,$id,$this->items[$id],$amounts[$id]);
            }
        }
        $request->session()->put("cart", $this);
    }

    /**
     * Changes the amount for this item and changes the total.
     * 
     * @param int $id = id of the item.
     * @param $item = The item itself.
     * @param int $newAmount = The new amount of the item.
     */
    public function changeAmountOfItem(Request $request,$id,$item,$newAmount){
        if($newAmount <= 0){
            $this->deleteItemFromCart($request,$id);
        }else{
            $this->totalPrice -= $item->price;
            $this->totalQty -= $item->qty;
            $this->totalQty += $newAmount;
            $item->qty = $newAmount;
            $item->price = $item->qty * $item->item->product_price;
            $this->totalPrice += $item->price;
            $this->items[$id] = $item;
        }
    }

    /**
     * Deletes the item form the list.
     * 
     * @param int $id = id of the item.
     */
    public function deleteItemFromCart(Request $request, $id){
        $this->totalQty -= $this->items[$id]->qty;
        $this->totalPrice -= $this->items[$id]->price;
        unset($this->items[$id]);
        $request->session()->put("cart", $this);
    }
} 