// State
//  data ...
// Mutations
//
//
// Actions
import usergroup from "./modules/usergroup.js";
import user from "./modules/user.js";
import system from "../../../apps/modules/system/system.js";
export const store = new Vuex.Store({
    modules: {
        usergroup: usergroup,
        user: user,
        system: system
    }
});
