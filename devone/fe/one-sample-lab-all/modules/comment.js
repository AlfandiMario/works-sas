import * as api from "../api/comment.js"

export default {
   namespaced: true,
   state: {
      show: false,
      loading: false,
      patient: {},
      history: [],
      comment: '',
      error: ''
   },
   mutations: {
      update_show(state, val) {
         state.show= val     
      },
      update_loading(state, val) {
         state.loading= val
      },
      update_patient(state, val) {
         state.patient= val
      },
      update_comment(state, val) {
         state.comment= val
      },
      update_history(state, val) {
         state.history= val
      },
      update_error(state, val) {
         state.error= val
      },
   },
   actions: {
      async load(context) {
         context.commit("update_loading", true)
         try {
            let prm = {
               T_OrderHeaderID: context.state.patient.T_OrderHeaderID,
               token: one_token()
            }
            let resp = await api.load(prm)
            if (resp.status != "OK") {
               context.commit("update_loading", false)
               context.commit("update_error", resp.message)
            } else {
               context.commit("update_loading", false)
               context.commit("update_error", "")
               context.commit("update_history", resp.data.data)
            }
         } catch (e) {
            context.commit("update_loading", false)
            context.commit("update_error", e.message)
         }
      },
      async save(context) {
         context.commit("update_loading", true)
         try {
            let prm = {
               T_OrderHeaderID: context.state.patient.T_OrderHeaderID,
               T_OrderHeaderSamplingNote : context.state.patient.T_OrderHeaderSamplingNote,
               token: one_token()
            }
            let resp = await api.save(prm)
            if (resp.status != "OK") {
               context.commit("update_loading", false)
               context.commit("update_error", resp.message)
            } else {
               context.commit("update_loading", false)
               context.commit("update_error", "")
            }
         } catch (e) {
            context.commit("update_loading", false)
            context.commit("update_error", e.message)
         }
      }
   }
}
