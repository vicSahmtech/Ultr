<?php

namespace App\Http\Controllers\Dashboard\Coupons;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class CouponsController extends Controller
{
    protected function index()
    {

        if (request()->ajax()) {

            $coupons = Coupon::all();
            return DataTables::of($coupons)
                ->addColumn('title', function ($row) {
                    return $row->title;
                })
                ->addColumn('value', function ($row) {
                    return $row->type == 'percentage' ? $row->value . '%' : $row->value . ' ريال سعودي ';
                })
                ->addColumn('image', function ($row) {
                    return '<img class="img-fluid" src="' . asset($row->image) . '"/>';
                })
                ->addColumn('status', function ($row) {
                    $checked = '';
                    if ($row->active == 1) {
                        $checked = 'checked';
                    }
                    return '<label class="switch s-outline s-outline-info  mb-0">
                        <input type="checkbox" id="customSwitchStatus" data-id="' . $row->id . '" ' . $checked . '>
                        <span class="slider round"></span>
                        </label>';
                })
                ->addColumn('control', function ($row) {

                    $html = '
                    <a href="' . route('dashboard.coupons.viewSingleCoupon', ['id' => $row->id]) . '" class="mr-2 btn btn-outline-primary btn-sm"><i class="far fa-eye fa-2x"></i> </a>
                    <a href="' . route('dashboard.coupons.edit', $row->id) . '"  id="edit-coupon" class="mr-2 btn btn-outline-warning btn-sm"><i class="far fa-edit fa-2x"></i> </a>

                                <a data-href="' . route('dashboard.coupons.destroy', $row->id) . '" data-id="' . $row->id . '" class="mr-2 btn btn-outline-danger btn-delete btn-sm">
                            <i class="far fa-trash-alt fa-2x"></i>
                    </a>
                                ';

                    return $html;
                })
                // ->addColumn('control', function ($row) {

                //     $html = '
                //     <a href="' . route('dashboard.coupons.show', $row->id) . '" class="mr-2 btn btn-outline-primary btn-sm">
                //             <i class="far fa-eye fa-2x"></i>

                //     </a>
                //     <a href="' . route('dashboard.coupons.edit', $row->id) . '"  id="edit-coupon" class="mr-2 btn btn-outline-warning btn-sm" data-id="' . $row->id . '"
                //           >
                //             <i class="far fa-edit fa-1x"></i>
                //        </a>

                //                 <a data-table_id="html5-extension" data-href="' . route('dashboard.coupons.destroy', $row->id) . '" data-id="' . $row->id . '" class="mr-2 btn btn-outline-danger btn-sm btn-delete btn-sm delete_tech">
                //             <i class="far fa-trash-alt fa-1x"></i>
                //     </a>';
                //     return $html;
                // })
                ->rawColumns([
                    'title',
                    'value',
                    'image',
                    'status',
                    'control',
                ])
                ->make(true);
        }

        return view('dashboard.coupons.index');
    }

    protected function create()
    {
        $categories = Category::all();
        $services = Service::all();
        return view('dashboard.coupons.create', compact('categories', 'services'));
    }
    protected function store(Request $request)
    {
        $rules = [
            'title_ar' => 'required|string|min:3|max:100',
            'title_en' => 'required|string|min:3|max:100',
            'type' => 'required|in:static,percentage',
            'value' => 'required|numeric',
            'start' => 'required|date',
            'end' => 'required|date',
            'times_limit' => 'required|numeric',
            'user_times' => 'required|numeric',
            'code' => 'nullable|string',
            'description_ar' => 'nullable|string|min:3',
            'description_en' => 'nullable|string|min:3',
            'image' => 'required|image|mimes:jpeg,jpg,png,gif',
            'is_hidden' => 'nullable|in:on,off',

        ];

        if ($request->sale_area == 'category') {
            $rules['category_id'] = 'required|exists:categories,id';
        }
        if ($request->sale_area == 'service') {
            $rules['service_id'] = 'required|exists:services,id';
        }
        $validated = Validator::make($request->all(), $rules);
        if ($validated->fails()) {
            return redirect()->back()->withErrors($validated->errors());
        }
        $validated = $validated->validated();
        if (!$validated['code']) {
            $last = Coupon::query()->latest()->first()?->id;
            $validated['code'] = 'coupon2023-' . $last ? $last + 1 : 1;
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $request->image->move(storage_path('app/public/images/coupons/'), $filename);
            $validated['image'] = 'storage/images/coupons' . '/' . $filename;
        }

        if ($request['is_hidden'] && $request['is_hidden'] == 'on') {
            $validated['is_hidden'] = 1;
        } else {
            $validated['is_hidden'] = 0;
        }
        Coupon::query()->create($validated);
        session()->flash('success');
        return redirect()->route('dashboard.coupons.index');
    }
    protected function edit($id)
    {
        $coupon = Coupon::query()->find($id);
        $categories = Category::all();
        $services = Service::all();
        return view('dashboard.coupons.edit', compact('coupon', 'categories', 'services'));
    }
    protected function update(Request $request, $id)
    {
        $coupon = Coupon::query()->findOrFail($id);
        $rules = [
            'title_ar' => 'required|string|min:3|max:100',
            'title_en' => 'required|string|min:3|max:100',
            'type' => 'required|in:static,percentage',
            'value' => 'required|numeric',
            'start' => 'required|date',
            'end' => 'required|date',
            'times_limit' => 'required|numeric',
            'user_times' => 'required|numeric',
            'is_hidden' => 'nullable|in:on,off',
            'code' => 'nullable|string',
            'description_ar' => 'nullable|string|min:3',
            'description_en' => 'nullable|string|min:3',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif',

        ];
        if ($request->sale_area == 'category') {
            $rules['category_id'] = 'required|exists:categories,id';
        }
        if ($request->sale_area == 'service') {
            $rules['service_id'] = 'required|exists:services,id';
        }
        $validated = Validator::make($request->all(), $rules);
        if ($validated->fails()) {
            return redirect()->back()->withErrors($validated->errors());
        }
        $validated = $validated->validated();
        if (!$validated['code']) {
            $last = Coupon::query()->latest()->first()?->id;
            $validated['code'] = 'coupon2023-' . $last ? $last + 1 : 1;
        }
        if (isset($validated['category_id']) && $validated['category_id']) {
            $validated['service_id'] = null;
        } else if (isset($validated['service_id']) && $validated['service_id']) {
            $validated['category_id'] = null;
        } else {
            $validated['service_id'] = null;
            $validated['category_id'] = null;
        }

        if ($request->hasFile('image')) {
            if (File::exists(public_path($coupon->image))) {
                File::delete(public_path($coupon->image));
            }
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $request->image->move(storage_path('app/public/images/coupons/'), $filename);
            $validated['image'] = 'storage/images/coupons' . '/' . $filename;
        }
        if ($request['is_hidden'] && $request['is_hidden'] == 'on') {
            $validated['is_hidden'] = 1;
        } else {
            $validated['is_hidden'] = 0;
        }
        $coupon->update($validated);
        session()->flash('success');
        return redirect()->route('dashboard.coupons.index');
    }
    protected function destroy($id)
    {
        $coupon = Coupon::query()->findOrFail($id);
        $coupon->delete();
        return [
            'success' => true,
            'msg' => __("dash.deleted_success")
        ];
    }
    protected function change_status(Request $request)
    {
        $coupon = Coupon::query()->where('id', $request->id)->first();
        if ($request->active == "false") {
            $coupon->active = 0;
        } else {
            $coupon->active = 1;
        }
        $coupon->save();
        return response('success');
    }
    protected function viewSingle()
    {
        $id = request()->query('id');

        $usage_filter = request()->query('usage');

        if (request()->ajax()) {
            $users = User::with(['couponUsers', 'couponUsers.coupon'])->select(['id', 'first_name', 'last_name', 'phone'])->withCount('couponUsers as usage')->orderBy('usage', 'desc');
            if ($usage_filter) {
                if ($usage_filter == 'notused') {
                    $users = $users->having('usage', '=', 0);
                } else {
                    $users = $users->having('usage', '>', 0);
                }
            }

            return DataTables::of($users)
                ->addColumn('name', function ($user) {
                    $name = $user->first_name . ' ' . $user->last_name;
                    return $name;
                })
                ->addColumn('phone', function ($user) {
                    return $user->phone;
                })
                ->addColumn('usage', function ($user) {


                    return $user->usage;
                })
                ->addColumn('control', function ($user) {

                    $html = '
                    <a href="' . route('dashboard.core.address.index', 'id=' . $user->id) . '" class="mr-2 btn btn-outline-primary btn-sm"><i class="far fa-address-book fa-2x"></i> </a>
                    <a href="' . route('dashboard.core.customer.edit', $user->id) . '" class="mr-2 btn btn-outline-warning btn-sm"><i class="far fa-edit fa-2x"></i> </a>

                                <a data-href="' . route('dashboard.core.customer.destroy', $user->id) . '" data-id="' . $user->id . '" class="mr-2 btn btn-outline-danger btn-delete btn-sm">
                            <i class="far fa-trash-alt fa-2x"></i>
                    </a>
                                ';

                    return $html;
                })
                ->rawColumns([

                    'name',
                    'phone',
                    'usage',
                    'control',
                ])
                ->make(true);
        }
        $coupon = Coupon::where('id', $id)->first();
        return view('dashboard.coupons.show', compact('id', 'coupon'));
    }
}
