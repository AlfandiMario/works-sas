// State
//  data ...
// Mutations
//
//
// Actions
import system from "../../../apps/modules/system/system.js";
import jpbreg from "./modules/jpbreg.js";
import jurnalgaji from "./modules/jurnalgaji.js";
import recrusive from "./modules/recrusive.js";
export const store = new Vuex.Store({
    modules: {
        recrusive: recrusive,
        jurnalgaji: jurnalgaji,
        jpbreg: jpbreg,
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