{{-- 40,000 + 15,000 = 55,000 taxable; 18% GST = 9,900, split 4,950 CGST + 4,950 SGST. --}}
<div class="rounded-xl bg-white p-6 text-[13px] text-gray-700 shadow-2xl sm:p-8">
    <div class="flex items-start justify-between gap-6">
        <div>
            <div class="text-base font-bold text-gray-900">{{ __('marketing.gst_mock.supplier') }}</div>
            <div>{{ __('marketing.gst_mock.supplier_address') }}</div>
            <div>{{ __('marketing.gst_mock.supplier_gstin') }}</div>
        </div>
        <div class="text-right">
            <div class="text-lg font-extrabold tracking-wide text-brand-600">{{ __('marketing.gst_mock.title') }}</div>
            <div class="font-semibold text-gray-900">{{ __('marketing.gst_mock.number') }}</div>
        </div>
    </div>

    <div class="mt-5 flex justify-between gap-6">
        <div>
            <div class="text-gray-400">{{ __('marketing.gst_mock.bill_to') }}</div>
            <div class="font-semibold text-gray-900">{{ __('marketing.gst_mock.customer') }}</div>
            <div>{{ __('marketing.gst_mock.customer_gstin') }}</div>
            <div>{{ __('marketing.gst_mock.place_of_supply') }}</div>
        </div>
        <div class="text-right">
            <div class="text-gray-400">{{ __('marketing.gst_mock.date_label') }}</div>
            <div>{{ __('marketing.gst_mock.date') }}</div>
        </div>
    </div>

    <table class="mt-5 w-full">
        <thead>
            <tr class="bg-brand-600 text-left text-white">
                <th class="px-2 py-1.5 font-semibold">{{ __('marketing.gst_mock.item') }}</th>
                <th class="px-2 py-1.5 text-right font-semibold">{{ __('marketing.gst_mock.qty') }}</th>
                <th class="hidden px-2 py-1.5 text-right font-semibold sm:table-cell">{{ __('marketing.gst_mock.gst') }}</th>
                <th class="px-2 py-1.5 text-right font-semibold">{{ __('marketing.gst_mock.amount') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <tr>
                <td class="px-2 py-2">
                    <div class="text-gray-900">{{ __('marketing.gst_mock.line_one') }}</div>
                    <div class="text-xs text-gray-400">{{ __('marketing.gst_mock.line_one_code') }}</div>
                </td>
                <td class="px-2 py-2 text-right">1.00</td>
                <td class="hidden px-2 py-2 text-right sm:table-cell">7,200.00<div class="text-xs text-gray-400">18%</div></td>
                <td class="px-2 py-2 text-right">40,000.00</td>
            </tr>
            <tr>
                <td class="px-2 py-2">
                    <div class="text-gray-900">{{ __('marketing.gst_mock.line_two') }}</div>
                    <div class="text-xs text-gray-400">{{ __('marketing.gst_mock.line_two_code') }}</div>
                </td>
                <td class="px-2 py-2 text-right">1.00</td>
                <td class="hidden px-2 py-2 text-right sm:table-cell">2,700.00<div class="text-xs text-gray-400">18%</div></td>
                <td class="px-2 py-2 text-right">15,000.00</td>
            </tr>
        </tbody>
    </table>

    <dl class="mt-4 ml-auto grid max-w-60 grid-cols-2 gap-y-1 text-right">
        <dt>{{ __('marketing.gst_mock.sub_total') }}</dt><dd>55,000.00</dd>
        <dt>{{ __('marketing.gst_mock.cgst') }}</dt><dd>4,950.00</dd>
        <dt>{{ __('marketing.gst_mock.sgst') }}</dt><dd>4,950.00</dd>
        <dt class="border-t border-gray-200 pt-1 font-bold text-gray-900">{{ __('marketing.gst_mock.total') }}</dt>
        <dd class="border-t border-gray-200 pt-1 font-bold text-gray-900">₹64,900.00</dd>
    </dl>
</div>
