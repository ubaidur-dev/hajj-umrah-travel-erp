<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Support\Pk;
use App\Support\Search;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:150', 'phone' => Pk::phone(), 'whatsapp' => Pk::phone(),
            'email' => Pk::email(), 'cnic' => Pk::cnic(), 'passport_no' => Pk::passport(),
            'city' => 'nullable|string|max:80', 'address' => 'nullable|string', 'notes' => 'nullable|string',
        ];
    }

    protected function messages(): array
    {
        return Pk::messages();
    }

    public function index(Request $r)
    {
        $q = Customer::query()->withCount('bookings');
        Search::apply($q, $r->q, ['name', 'phone', 'whatsapp', 'email', 'cnic', 'passport_no', 'city', 'address'], [], ['name', 'phone', 'cnic', 'passport_no']);

        if ($r->city) {
            $q->where('city', $r->city);
        }

        return view('customers.index', ['cities' => Customer::whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city'), 'customers' => $q->latest('id')->paginate(10)->withQueryString()]);
    }

    /** Used by the "Add new customer" pop-up inside the booking form. */
    public function quick(Request $r)
    {
        $c = Customer::create($r->validate(['name' => 'required|string|max:150', 'phone' => Pk::phone(), 'passport_no' => Pk::passport(), 'cnic' => Pk::cnic()], Pk::messages()));
        ActivityLog::record('create', "Customer added: {$c->name}", $c);

        return response()->json(['id' => $c->id, 'label' => $c->name.($c->phone ? ' · '.$c->phone : '')]);
    }

    public function create() { return view('customers.form', ['customer' => new Customer]); }

    public function store(Request $r)
    {
        $c = Customer::create($r->validate($this->rules(), $this->messages()));
        ActivityLog::record('create', "Customer added: {$c->name}", $c);

        return redirect()->route('customers.show', $c)->with('ok', 'Customer saved.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['bookings.package', 'invoices']);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer) { return view('customers.form', compact('customer')); }

    public function update(Request $r, Customer $customer)
    {
        $customer->update($r->validate($this->rules(), $this->messages()));

        return redirect()->route('customers.show', $customer)->with('ok', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->bookings()->exists()) {
            return back()->with('err', 'This customer has bookings and cannot be deleted.');
        }
        $customer->delete();

        return redirect()->route('customers.index')->with('ok', 'Customer deleted.');
    }
}
