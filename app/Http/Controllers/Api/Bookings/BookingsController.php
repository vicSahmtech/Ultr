<?php

namespace App\Http\Controllers\Api\Bookings;

use App\Http\Controllers\Controller;
use App\Http\Resources\Booking\BookingResource;
use App\Http\Resources\Order\StatusResource;
use App\Http\Resources\Technician\home\VisitsResource;
use App\Models\Booking;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitsStatus;
use App\Support\Api\ApiResponse;

class BookingsController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('localization');
    }
    protected function myBookings()
    {
        $user = User::with('bookings.booking_status', 'bookings.service.BookingSetting')->where('id', auth('sanctum')->user()->id)->first();
        $this->body['bookings'] = BookingResource::collection($user->bookings()->orderBy('id', 'DESC')->get());
        $this->body['all_statuses'] = StatusResource::collection(VisitsStatus::all());
        return self::apiResponse(200, null, $this->body);
    }
    protected function bookingDetails($id)
    {
        $visit_id = Booking::query()->with('visit')->find($id)->visit?->id;
        $order = Visit::whereHas('booking', function ($q) {
            $q->whereHas('customer')->whereHas('address');

        })->with('booking', function ($q) {
            $q->with(['service' => function ($q) {
                $q->with('category');
            }, 'customer', 'address']);

        })->with('status')->where('id', $visit_id)->first();
        $this->body['visit'] = VisitsResource::make($order);

        return self::apiResponse(200, null, $this->body);
    }
    // protected function bookingDetails($id){
    //     $visit_id = Booking::query()->with('visit')->find($id);
    //     if ($visit_id->visit){
    //         $visit_id = $visit_id->visit->id;
    //         $order = Visit::whereHas('booking', function ($q) {
    //             $q->whereHas('customer')->whereHas('address');

    //         })->with('booking', function ($q) {
    //             $q->with(['service' => function ($q) {
    //                 $q->with('category');
    //             },'customer','address']);

    //         })->with('status')->where('id', $visit_id)->first();
    //         $this->body['visit'] = VisitsResource::make($order);
    //         return self::apiResponse(200, null, $this->body);

    //     }else{
    //         $msg = __('api.visit not found');
    //         return self::apiResponse(404, $msg, null);
    //     }
    // }
}
