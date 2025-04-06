// State
//  data ...
// Mutations
//
//
// Actions

import patient from "./modules/patient.js";
import payment from "./modules/payment.js";
import system from "../../../apps/modules/system/system.js";
export const store = new Vuex.Store({
    modules: {
        patient: patient,
        payment: payment,
        system:system
    },
    state: {
        tab_selected: 'pasien-dokter'
    },
    mutations: {
        change_tab(state, ntab) {
            state.tab_selected = ntab
        }
    },
    actions: {
        change_tab(context, ntab) {
            context.commit('change_tab', ntab)
        }
    }
});