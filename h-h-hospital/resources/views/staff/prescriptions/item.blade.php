<fieldset class="space-y-4 rounded-xl border border-line p-4 sm:p-5" data-medicine-row>
    <legend class="px-2 text-sm font-semibold" data-medicine-heading>{{ __('prescription.row', ['number' => $index + 1]) }}</legend>
    <div class="grid gap-4 sm:grid-cols-2"><x-form.input :name="'items['.$index.'][medicine_name]'" :id="'medicine-'.$index.'-medicine_name'" :label="__('prescription.medicine_name')" :value="$row['medicine_name'] ?? ''" data-medicine-field="medicine_name" maxlength="150" autocomplete="off" />
        <x-form.input :name="'items['.$index.'][dose]'" :id="'medicine-'.$index.'-dose'" :label="__('prescription.dose')" :value="$row['dose'] ?? ''" data-medicine-field="dose" maxlength="100" /></div>
    <div class="grid gap-4 sm:grid-cols-2"><x-form.input :name="'items['.$index.'][frequency]'" :id="'medicine-'.$index.'-frequency'" :label="__('prescription.frequency')" :value="$row['frequency'] ?? ''" data-medicine-field="frequency" maxlength="100" />
        <x-form.input :name="'items['.$index.'][duration]'" :id="'medicine-'.$index.'-duration'" :label="__('prescription.duration')" :value="$row['duration'] ?? ''" data-medicine-field="duration" maxlength="100" /></div>
    <x-form.input :name="'items['.$index.'][instructions]'" :id="'medicine-'.$index.'-instructions'" :label="__('prescription.instructions')" :value="$row['instructions'] ?? ''" data-medicine-field="instructions" maxlength="1000" />
    <button type="button" class="button button-ghost button-sm" data-remove-medicine>{{ __('prescription.remove') }}</button>
</fieldset>
