@extends('layouts.app')
@section('content')
<div id="app" class="bg-gray-900">
    <nav-bar>
    </nav-bar>
  <div class="mx-auto max-w-7xl">
    <div class="bg-gray-900 py-10">
      <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
          <div class="sm:flex-auto">
            <h1 class="text-base font-semibold text-white">Accounts</h1>
            <p class="mt-2 text-sm text-gray-300">A list of all the accounts.</p>
          </div>
          <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <button type="button" class="block rounded-md bg-indigo-500 px-3 py-2 text-center text-sm font-semibold text-white hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">Add account</button>
          </div>
        </div>
        <account-table></account-table>
      </div>
    </div>
  </div>
</div>
@endsection
