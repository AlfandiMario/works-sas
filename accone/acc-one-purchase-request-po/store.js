import requester from "./modules/requester.js";
import system from "../../../apps/modules/system/system.js";
export const store = new Vuex.Store({
  modules: {
    requester: requester,
    system: system,
  },
  state: {},
  mutations: {},
  actions: {},
});
