import requester from "./modules/requester.js";
import transfer from "./modules/transfer.js";
import system from "../../../apps/modules/system/system.js";
export const store = new Vuex.Store({
  modules: {
    requester: requester,
    transfer: transfer,
    system: system,
  },
  state: {},
  mutations: {},
  actions: {},
});
