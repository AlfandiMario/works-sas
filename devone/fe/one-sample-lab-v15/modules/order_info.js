import * as api from "../api/order_info.js"

export default {
    namespaced: true,
    state: {
        show:false,
        loading: false,
        error_message: '',
	orders: []
    },
    mutations: {
        update_loading(state, val) {
            state.loading = val
        },
        update_show(state, val) {
            state.show= val
        },
        update_error_message(state, val) {
            state.error_message= val
        },
	update_orders(state,val) {
            state.orders = val
	}
    },
    actions: {
        async search(context, prm) {
            context.commit("update_loading", true)
            try {
                prm.token = one_token()
                context.commit("update_loading",true)
                context.commit("update_show",true)
                let resp = await api.search(prm)
                if (resp.status != "OK") {
                    context.commit("update_error_message", resp.message)
                } else {
                    context.commit("update_error_message", "")
                    context.commit("update_orders", resp.data)
                    context.commit("update_loading",false)
                }
            } catch (e) {
                context.commit("update_error_message", e.message)
            }
        },
    }
}
