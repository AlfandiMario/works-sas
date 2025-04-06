import * as api from "../api/req.js"

export default {
   namespaced: true,
   state: {
      show: false,
      loading: false,
      patient: {},
      requirements: '',
      note: {},
      error: ''
   },
   mutations: {
      update_show(state, val) {
         state.show= val     
      },
      update_note(state, val) {
         state.note= val     
      },
      update_loading(state, val) {
         state.loading= val
      },
      update_patient(state, val) {
         state.patient= val
      },
      update_requirements(state, val) {
         state.requirements= val
      },
      update_error(state, val) {
         state.error= val
      },
   },
   actions: {
      async load(context) {
         context.commit("update_loading", true)
         context.commit("update_note", {})
         try {
            let prm = {
               T_OrderHeaderID: context.state.patient.T_OrderHeaderID,
               token: one_token()
            }
            let resp = await api.req(prm)
            if (resp.data.status != "OK") {
               context.commit("update_loading", false)
               context.commit("update_error", resp.message)
            } else {
               context.commit("update_loading", false)
               context.commit("update_error", "")
               context.commit("update_requirements", resp.data.data)
               context.commit("update_note", resp.data.note)
            }
         } catch (e) {
            context.commit("update_loading", false)
            context.commit("update_error", e.message)
         }
      }
   }
}
