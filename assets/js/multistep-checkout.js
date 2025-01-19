(function ($) {
    'use strict';

    $(document).ready(function () {
        const countryField = $('#billing_country');
        const stateFieldContainer = $('#billing_state_field');
        const savedCountry = localStorage.getItem('billing_country');
        const savedState = localStorage.getItem('billing_state');
        const states = wc_country_states.states;

        // Set saved country and state on page load
        if (savedCountry) {
            countryField.val(savedCountry).change();
        }

        function updateStateField(clearState = true) {
            const selectedCountry = countryField.val();

            // Clear previous state field content
            stateFieldContainer.empty();

            // Add label for State/Region
            const stateLabel = $('<label>', {
                for: 'billing_state',
                text: 'State/Region',
                class: 'form-label',
            });
            stateFieldContainer.append(stateLabel);

            if (states[selectedCountry] && Object.keys(states[selectedCountry]).length > 0) {
                // Create a select dropdown for states
                const stateSelect = $('<select>', {
                    id: 'billing_state',
                    name: 'billing_state',
                    class: 'state_select input-text',
                    required: true,
                });

                // Add a placeholder option
                stateSelect.append($('<option>', { value: '', text: 'Select State/Region' }));

                // Add state options
                $.each(states[selectedCountry], function (code, name) {
                    const option = $('<option>', { value: code, text: name });

                    // Restore selected state if it matches saved value and clearState is false
                    if (!clearState && savedState && savedState === code) {
                        option.prop('selected', true);
                    }

                    stateSelect.append(option);
                });

                stateFieldContainer.append(stateSelect);
            } else {
                // Create a text input for states
                const stateInput = $('<input>', {
                    type: 'text',
                    id: 'billing_state',
                    name: 'billing_state',
                    class: 'input-text',
                    required: true,
                    placeholder: 'Enter State/Region',
                });

                if (!clearState && savedState) {
                    stateInput.val(savedState); // Restore saved value if clearState is false
                }

                stateFieldContainer.append(stateInput);
            }

            // Clear state saved in local storage if clearState is true
            if (clearState) {
                localStorage.removeItem('billing_state');
            }
        }

        // Handle country change event
        countryField.on('change', function () {
            updateStateField(true);

            // Save selected country to local storage
            localStorage.setItem('billing_country', countryField.val());
        });

        // Save state value to local storage on change
        stateFieldContainer.on('change', '#billing_state', function () {
            localStorage.setItem('billing_state', $(this).val());
        });

        // Initialize the state field
        updateStateField(false);
    });
})(jQuery);
