// State
//  data ...
// Mutations
//
//
// Actions
import samplecall from "./modules/samplecall.js";
import order_info from "./modules/order_info.js";
import req from "./modules/req.js";
import comment from "./modules/comment.js";
import system from "../../../apps/modules/system/system.js";
export const store = new Vuex.Store({
    modules: {
        samplecall: samplecall,
        system: system,
        order_info: order_info,
        req,
        comment
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
