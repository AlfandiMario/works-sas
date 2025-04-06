// State
//  data ...
// Mutations
//
//
// Actions
import system from "../../../apps/modules/system/system.js";
import recrusive from "./modules/recrusive.js";
import terimaBarang from "./modules/terimaBarang.js";
import jurnalumum from "./modules/jurnalumum.js";
export const store = new Vuex.Store({
    modules: {
        recrusive: recrusive,
        system: system,
        terimaBarang: terimaBarang,
        jurnalumum: jurnalumum
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