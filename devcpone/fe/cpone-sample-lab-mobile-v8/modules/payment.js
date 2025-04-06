// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/payment.js"

export default  {
   namespaced: true,
   state: {
      lookup_status:0,
      lookup_error_message:'',
      types: [],
      total_payment:0,
      reload_after_save: false,
      dialog_pay_success:false,
      paynumber :'',
      notes :[],
      dialog_delete:false,
      msg_delete:'',
      note_delete:'',
      nota_delete:{},
      open_print_note:false,
      idx:0,
      last_payments:{}
   },
   mutations: {
      update_lookup_error_message(state,val) {
         state.lookup_error_message = val
      },
      update_lookup_status(state,status) {
         state.lookup_status = status
      },
      update_types(state,data) {
         state.types = data.records
         state.total_type = data.total
      },
      update_selected_status(state,val) {
         state.selected_status=val 
      },
      update_total_payment(state,val) {
         state.total_payment=val 
      },
      update_reload_after_save(state,val) {
         state.reload_after_save=val 
      },
      update_dialog_pay_success(state,val) {
         state.dialog_pay_success=val 
      },
      update_paynumber(state,val) {
         state.paynumber=val 
      },
      update_notes(state,val) {
         state.notes=val 
      },
      update_dialog_delete(state,val) {
         state.dialog_delete=val 
      },
      update_note_delete(state,val) {
         state.note_delete=val 
      },
      update_msg_delete(state,val) {
         state.msg_delete=val 
      },
      update_nota_delete(state,val) {
         state.nota_delete=val 
      },
      update_open_print_note(state,val) {
         state.open_print_note=val 
      },
      update_idx(state,val) {
         state.idx=val 
      },
      update_last_payments(state,val) {
         state.last_payments=val 
      }
   },
   actions: {
      async lookup_type(context) {
         context.commit("update_lookup_status",1)
         try {
            let resp= await api.lookup_type(one_token())
            if (resp.status != "OK") {
               context.commit("update_lookup_status",3)
               context.commit("update_lookup_error_message",resp.message)
            } else {
               context.commit("update_lookup_status",2)
               context.commit("update_lookup_error_message","")
               let data = {
                  records : resp.data.records,
                  total: resp.data.total
               }
               context.commit("update_types",data)
            }
         } catch(e) {
            context.commit("update_lookup_status",3)
            context.commit("update_lookup_error_message",e.message )
         }
      },
      async pay(context,prm) {
         context.commit("update_lookup_status",1)
         try {
            prm.token = one_token()
            let resp= await api.pay(prm)
            if (resp.status != "OK") {
               context.commit("update_lookup_status",3)
               context.commit("update_lookup_error_message",resp.message)
            } else {
               context.commit("update_lookup_status",2)
               context.commit("update_lookup_error_message","")
               let data = {
                  records : resp.data.records.types,
                  total: resp.data.total
               }
               let xnumber = resp.data.records.data.numberx
               let id = resp.data.records.data.idx
               context.commit("update_types",data)
               context.commit("update_last_payments",prm.payments)
               context.commit("update_idx",id)
               context.commit("update_total_payment",0)
               context.commit("update_paynumber","Pembayaran nomor <span style='color:red'>"+xnumber+"</span> telah berhasil")
               context.commit("update_dialog_pay_success",true)
            }
         } catch(e) {
            context.commit("update_lookup_status",3)
            context.commit("update_lookup_error_message",e.message )
         }
      },
      async delete_note(context,prm) {
         context.commit("update_lookup_status",1)
         try {
            prm.token = one_token()
            let resp= await api.delete_note(prm)
            if (resp.status != "OK") {
               context.commit("update_lookup_status",3)
               context.commit("update_lookup_error_message",resp.message)
            } else {
               context.commit("update_lookup_status",2)
               context.commit("update_lookup_error_message","")
               let xmsg = "Nota nomor <span style='color:red'>"+prm.nota.note_number+"</span> telah dihapus"
               context.commit("update_msg_delete",xmsg)
               context.commit("update_note_delete",'')
               context.commit("update_nota_delete",{})
            }
         } catch(e) {
            context.commit("update_lookup_status",3)
            context.commit("update_lookup_error_message",e.message )
         }
      }
   }
}
