<?php

namespace Infinety\MenuBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Infinety\MenuBuilder\Http\Models\Menu;
use Infinety\MenuBuilder\Http\Models\MenuItems;
use Infinety\MenuBuilder\Http\Requests\NewMenuItemRequest;
use DB;

class MenuController extends Controller 
{
    /**
     * Return menu items for given menu
     *
     * @param   Request  $Request
     *
     * @return  Collection | json
     */
    public function items(Request $request)
    {
        if (!$request->has('menu')) {
            abort(503);
        }
        //  print_r($request->get('menu'));
        return Menu::find($request->get('menu'))->optionsMenu();
    }

    /**
     * Save menu items when reordering
     *
     * @param   Request  $request
     *
     * @return  json
     */
    public function saveItems(Request $request)
    { 
        $menu = Menu::find($request->get('menu'));
        $items = $request->get('items');
        $i = 1;
        foreach ($items as $item) {
            $this->saveMenuItem($i, $item);
            $i++;
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
    /**
     * Create new menu item
     *
     * @param   NewMenuItemRequest  $request
     *
     * @return  json
     */
    public function createNew(NewMenuItemRequest $request)
    {
        // print_r($request->all());
        $data = $request->all();
        $data['order'] = MenuItems::where('id',$request->menu_id)->max('order') + 1;
        
        $menuItem = MenuItems::create($data);

        return response()->json([
        'success' => true,
         ]);
    }

    /**
     * Get menu item to edit
     *
     * @param   \Infinety\MenuBuilder\Http\Models\MenuItems  $item
     *
     * @return  json
     */
    public function edit(MenuItems $item)
    {
        // print_r($item);
        return $item->toJson();
    }

    public static function get_store_id(Request $request)
    {
        $domain = parse_url(request()->root())['host']; 
        $store = DB::table('stores')->select('id')->where('domain',$domain)->first();
        return $store->id;
    }
    
    public function get_categories(Request $request){
        $domain = parse_url(request()->root())['host']; 
        $store = DB::table('stores')->select('id')->where('domain',$domain)->first();
        $get_cat = DB::table('product_categories')
                   ->select('name','id','store_id')
                   ->where('store_id', $store->id)
                   ->where('display_on','LIKE','%WebMenu%')
                   ->get();
        return $get_cat;
    }

    /**
     * Update the given menu item
     *
     * @param   \Infinety\MenuBuilder\Http\Models\MenuItems  $item
     * @param   NewMenuItemRequest  $request
     *
     * @return  json
     */
    public function update(MenuItems $item, NewMenuItemRequest $request)
    {
        // print_r($request->all());
        $item->update($request->all());

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Destroy current menu item and all his childrens
     *
     * @param   \Infinety\MenuBuilder\Http\Models\MenuItems  $item
     *
     * @return  json
     */
    public function destroy(MenuItems $item)
    {
        $item->children()->delete();
        $item->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Save the menu item
     *
     * @param   int  $order
     * @param   array  $item
     * @param   int  $parentId
     *
     */
    private function saveMenuItem($order, $item, $parentId = null)
    {
       
        $menuItem = MenuItems::find($item['id']);
        $menuItem->order = $order;
        $menuItem->parent_id = $parentId;
        $menuItem->save();

        $this->checkChildren($item);
    }

    /**
     * Recurisve save menu items childrens
     *
     * @param   array  $item
     *
     */
    private function checkChildren($item)
    {
        if (count($item['children']) > 0) {
            $i = 1;
            foreach ($item['children'] as $child) {
                $this->saveMenuItem($i, $child, $item['id']);
                $i++;
            }
        }
    }
}
