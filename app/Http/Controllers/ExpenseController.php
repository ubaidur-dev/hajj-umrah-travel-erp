<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Support\Search;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    protected function rules(): array
    {
        return [
            'category' => 'required|string|max:50', 'title' => 'required|string|max:200',
            'amount' => 'required|numeric|min:1', 'expense_date' => 'required|date', 'method' => 'required|in:'.implode(',', array_keys(Payment::METHODS)),
            'reference' => 'nullable|string|max:100', 'note' => 'nullable|string',
        ];
    }

    public function index(Request $r)
    {
        $q = Expense::query();
        Search::apply($q, $r->q, ['title', 'category', 'reference', 'note', 'method'], [], ['title', 'category']);
        if ($r->category) {
            $q->where('category', $r->category);
        }
        if ($r->from) {
            $q->whereDate('expense_date', '>=', $r->from);
        }
        if ($r->to) {
            $q->whereDate('expense_date', '<=', $r->to);
        }
        $total = (clone $q)->reorder()->sum('amount');

        return view('expenses.index', ['categories' => collect(array_keys(Expense::CATEGORIES))->merge(Expense::query()->distinct()->pluck('category'))->unique()->values(), 'expenses' => $q->latest('expense_date')->latest('id')->paginate(10)->withQueryString(), 'total' => $total]);
    }

    public function create(Request $r)
    {
        return view('expenses.form', ['expense' => new Expense(['expense_date' => today(), 'method' => 'cash', 'category' => '']), ...$this->lookups()]);
    }

    public function store(Request $r)
    {
        Expense::create($r->validate($this->rules()) + ['created_by' => auth()->id()]);

        return redirect()->route('expenses.index')->with('ok', 'Expense saved.');
    }

    public function edit(Expense $expense) { return view('expenses.form', ['expense' => $expense, ...$this->lookups()]); }

    public function update(Request $r, Expense $expense)
    {
        $expense->update($r->validate($this->rules()));

        return redirect()->route('expenses.index')->with('ok', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('ok', 'Expense deleted.');
    }

    protected function lookups(): array
    {
        return ['categories' => collect(array_keys(Expense::CATEGORIES))->merge(Expense::query()->distinct()->pluck('category'))->unique()->values()];
    }
}
