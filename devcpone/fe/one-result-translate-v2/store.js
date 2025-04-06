// State
//  data ...
// Mutations
//
//
// Actions
import price from "./modules/price.js";
import resume from "./modules/resume.js";
import system from "../../../apps/modules/system/system.js";
export const store = new Vuex.Store({
    modules: {
        resume: resume,
        system: system
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