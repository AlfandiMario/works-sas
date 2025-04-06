// 1 => LOADING
// 2 => DONE
// 3 => ERROR

import * as api from "../api/api-transfer.js";

export default {
    namespaced: true,
    state: {
        user: one_user(),
        // Toolbar Header
        menuStartDate: false,
        menuEndDate: false,
        menuDateUse: false,
        dStartDate: new Date().toISOString().substring(0, 10),
        dEndDate: new Date().toISOString().substring(0, 10),
        fStartDate: moment().startOf('month').format("YYYY-MM-DD"), // awal bulan
        fEndDate: moment(new Date()).format("YYYY-MM-DD"), // sampai hari ini
        xRegional: { S_RegionalID: 0, S_RegionalName: "" },
        branchOptions: [],
        xBranch: { M_BranchCode: "", M_BranchName: "" },
        statusOptions: [
            { value: 0, title: "All" },
            { value: 1, title: "Draft" },
            { value: 2, title: "Pending" },
            { value: 3, title: "Approved" },
            { value: 4, title: "Rejected" },
            { value: 5, title: "Paid" },
            { value: 6, title: "Completed" },
        ],
        xStatus: { value: 0, title: "All" },
        fSearch: "",
        searchBranch: "",
        searchStatus: "",
        // Data Header / Main Table
        mainTable: [],
        detailTable: [],
        countMainTable: 0,
        countDetailTable: 0,
        mainTableLoadingStatus: 0,
        detailTableLoadingStatus: 0,
        mainTableErrorMessage: "",
        detailTableErrorMessage: "",
        mainTableCurrentPage: 1,
        mainTableLastId: -1,
        selectedMainTable: {},
        selectedDetailTable: {},
        // Open/Close Dialog Add Request
        dialogRequest: false,
        dDateUse: new Date().toISOString().substring(0, 10),
        xDateUse: moment(new Date()).format("YYYY-MM-DD"),
        xDescription: "",
        act: "new",

        // Show Detail Table



        // Dialog Add Detail
        xDescriptionDetail: "",
        xAmountRequest: 0,
        xEstimationPrice: 0,
        dialogDetail: false,

        xNoReferensi: "",
        vendorOptions: [],
        xVendor: "",
        itemTypeOptions: [],
        xItemType: "",
        xRegional: "",
        xPRID: 0,
        xNumber: "",
        xPRDID: 0,

        errMessage: "",
        successMessage: "",
        alertError: false,
        alertSuccess: false,
        errors: [],
        vendors: [],
        selectedVendor: { "SupplierID": "0", "SupplierCode": "All", "SupplierName": "All" },
        searchVendor: "",
        loadingAutocomplete: false,
        itemTypes: [],
        selectedItemType: { "ItemTypeID": "0", "ItemTypeName": "All" },
        searchItemType: ""
    },
    mutations: {
        updateUser(state, val) {
            state.user = val
        },
        updateBranchOptions(state, val) {
            state.branchOptions = val;
        },
        updateMenuStartDate(state, val) {
            state.menuStartDate = val;
        },
        updateMenuEndDate(state, val) {
            state.menuEndDate = val;
        },
        updateMenuDateUse(state, val) {
            state.menuDateUse = val;
        },
        updateDStartDate(state, val) {
            state.dStartDate = val;
        },
        updateDEndDate(state, val) {
            state.dEndDate = val;
        },
        updateFStartDate(state, val) {
            state.fStartDate = val;
        },
        updateFEndDate(state, val) {
            state.fEndDate = val;
        },
        updateFStatus(state, val) {
            state.fStatus = val;
        },
        updateFSearch(state, val) {
            state.fSearch = val;
        },
        updateStatusOptions(state, val) {
            state.statusOptions = val
        },
        updateXStatus(state, val) {
            state.xStatus = val;
        },
        updateSearchBranch(state, val) {
            state.searchBranch = val;
        },
        updateSearchStatus(state, val) {
            state.searchStatus = val
        },
        // Data Main Table
        updateMainTable(state, val) {
            state.mainTable = val;
        },
        updateCountMainTable(state, val) {
            state.countMainTable = val;
        },
        updateMainTableLoadingStatus(state, val) {
            state.mainTableLoadingStatus = val;
        },
        updateMainTableErrorMessage(state, val) {
            state.mainTableErrorMessage = val;
        },
        updateMainTableCurrentPage(state, val) {
            state.mainTableCurrentPage = val;
        },
        updateMainTableLastId(state, val) {
            state.mainTableLastId = val;
        },
        updateSelectedMainTable(state, val) {
            state.selectedMainTable = val;
        },
        // Dialog Add Request
        updateXBranch(state, val) {
            state.xBranch = val;
        },
        updateXDescription(state, val) {
            state.xDescription = val;
        },
        updateDialogRequest(state, val) {
            state.dialogRequest = val;
        },
        updateAct(state, val) {
            state.act = val;
        },

        // Dialog Add Detail
        updateXDescriptionDetail(state, val) {
            state.xDescriptionDetail = val;
        },
        updateXAmountRequest(state, val) {
            state.xAmountRequest = val;
        },
        updateXEstimationPrice(state, val) {
            state.xEstimationPrice = val;
        },

        updateDialogDetail(state, val) {
            state.dialogDetail = val;
        },



        updateDetailTable(state, val) {
            state.detailTable = val;
        },
        updateCountDetailTable(state, val) {
            state.countDetailTable = val;
        },
        updateDetailTableLoadingStatus(state, val) {
            state.detailTableLoadingStatus = val;
        },
        updateDetailTableErrorMessage(state, val) {
            state.detailTableErrorMessage = val;
        },

        updateSelectedDetailTable(state, val) {
            state.selectedDetailTable = val;
        },

        updateRegionalOptions(state, val) {
            state.regionalOptions = val;
        },

        updateDDateUse(state, val) {
            state.dDateUse = val;
        },
        updateXDateUse(state, val) {
            state.xDateUse = val;
        },
        updateXNoReferensi(state, val) {
            state.xNoReferensi = val;
        },
        updateVendorOptions(state, val) {
            state.vendorOptions = val
        },
        updateXVendor(state, val) {
            state.xVendor = val;
        },
        updateItemTypeOptions(state, val) {
            state.itemTypeOptions = val
        },
        updateXItemType(state, val) {
            state.xItemType = val
        },
        updateXRegional(state, val) {
            state.xRegional = val;
        },


        updateXPRID(state, val) {
            state.xPRID = val;
        },
        updateXNumber(state, val) {
            state.xNumber = val;
        },
        updateXPRDID(state, val) {
            state.xPRDID = val;
        },




        updateErrMessage(state, val) {
            state.errMessage = val;
        },
        updateSuccessMessage(state, val) {
            state.successMessage = val;
        },
        updateAlertError(state, val) {
            state.alertError = val;
        },
        updateAlertSuccess(state, val) {
            state.alertSuccess = val;
        },
        updateErrors(state, val) {
            state.errors = val;
        },
        updateVendors(state, val) {
            state.vendors = val
        },
        updateSelectedVendor(state, val) {
            state.selectedVendor = val
        },
        updateSearchVendor(state, val) {
            state.searchVendor = val
        },
        updateLoadingAutocomplete(state, val) {
            state.loadingAutocomplete = val
        },
        updateItemTypes(state, val) {
            state.itemTypes = val
        },
        updateSelectedItemType(state, val) {
            state.selectedItemType = val
        },
        updateSearchItemType(state, val) {
            state.searchItemType = val
        }
    },
    actions: {
        async getBranches(context) {
            let pload = {
                token: one_token(),
                S_RegionalID: context.state.user.S_RegionalID
            }
            let response = await api.getBranches(pload);
            if (response.status === "OK") {
                let data = {
                    records: response.data.records,
                };
                context.commit("updateBranchOptions", data.records);
            }
        },

        async getStatus(context) {
            let pload = {
                token: one_token(),
            }
            let response = await api.getStatus(pload);
            if (response.status === "OK") {
                let data = {
                    records: response.data.records,
                };
                context.commit("updateStatusOptions", data.records);
            }
        },

        async search(context) {
            context.commit("updateMainTableLoadingStatus", 1);
            try {
                var payload = {
                    token: one_token(),
                    startDate: context.state.fStartDate,
                    endDate: context.state.fEndDate,
                    M_BranchCode: context.state.xBranch.M_BranchCode,
                    StatusName: context.state.xStatus.title,
                    currentPage: context.state.mainTableCurrentPage,
                    search: context.state.fSearch
                };

                let response = await api.search(payload);
                if (response.status != "OK") {
                    context.commit("updateMainTableLoadingStatus", 3);
                    context.commit("updateMainTableErrorMessage", response.message);
                } else {
                    context.commit("updateMainTableLoadingStatus", 2);
                    context.commit("updateMainTableErrorMessage", "");
                    let data = {
                        records: response.data.records,
                        total: response.data.totalPage,
                    };
                    context.commit("updateMainTable", data.records);
                    context.commit("updateCountMainTable", data.total);
                }
            } catch (e) {
                context.commit("updateMainTableLoadingStatus", 3);
                context.commit("updateMainTableErrorMessage", e.message);
            }
        },

        async searchDetail(context) {
            context.commit("updateDetailTableLoadingStatus", 1);

            try {
                var payload = {
                    token: one_token(),
                    PRID: context.state.selectedMainTable.PurchaseRequestDirectID,
                };

                let response = await api.searchDetail(payload);
                if (response.status != "OK") {
                    context.commit("updateDetailTableLoadingStatus", 3);
                    context.commit("updateDetailTableErrorMessage", response.message);
                } else {
                    context.commit("updateDetailTableLoadingStatus", 2);
                    context.commit("updateDetailTableErrorMessage", "");
                    let data = {
                        records: response.data.records,
                        total: response.data.total,
                    };

                    context.commit("updateDetailTable", data.records);
                    context.commit("updateCountDetailTable", data.total);
                }
            } catch (e) {
                context.commit("updateDetailTableLoadingStatus", 3);
                context.commit("updateDetailTableErrorMessage", e.message);
            }
        },
        async getRegional(context) {
            let response = await api.getRegional({ token: one_token() });
            if (response.status === "OK") {
                let data = {
                    records: response.data.records,
                };

                context.commit("updateRegionalOptions", data.records);
            }
        },

        async saveRequest(context, payload) {
            try {
                payload.token = one_token();
                console.log("Payload", payload)
                let response = await api.saveRequest(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                } else {
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                        total: response.data.total,
                    };

                    if (data.total !== -1) {
                        context.commit("updateErrors", []);
                        context.commit("updateAlertSuccess", true);
                        context.commit("updateDialogRequest", false);
                        var msg = `Purchase Request baru dengan kode ${data.records[0].T_GoodsTransferNum} sudah tersimpan`;
                        context.commit("updateSuccessMessage", msg);
                        context.dispatch("search");
                        context.commit("updateSelectedMainTable", data.records[0]);
                        context.dispatch("searchDetail");
                    } else {
                        context.commit("updateErrors", response.data.errors);
                    }
                    console.log("Resp Save Request", response)
                }
            } catch (e) {
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async updateRequest(context, payload) {
            try {
                payload.token = one_token();

                let response = await api.updateRequest(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                    context.commit("updateAlertError", true);
                } else {
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                        total: response.data.total,
                    };

                    if (data.total !== -1) {
                        context.commit("updateErrors", []);
                        context.commit("updateAlertSuccess", true);
                        context.commit("updateDialogRequest", false);
                        var msg = `Perubahan Purchase Request dengan kode ${data.records[0].PurchaseRequestDirectNumber} sudah tersimpan`;
                        context.commit("updateSuccessMessage", msg);
                        context.dispatch("search");
                        context.commit("updateSelectedMainTable", data.records[0]);
                        context.dispatch("searchDetail");
                    } else {
                        context.commit("updateErrors", response.data.errors);
                    }
                }
            } catch (e) {
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async deleteRequest(context, payload) {
            try {
                payload.token = one_token();

                let response = await api.deleteRequest(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                    context.commit("updateAlertError", true);
                } else {
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                        total: response.data.total,
                    };

                    if (data.total !== -1) {
                        context.commit("updateErrors", []);
                        context.commit("updateAlertSuccess", true);
                        context.commit("updateDialogRequest", false);
                        var msg = `Purchase Request dengan kode ${payload.PRNumber} sudah dihapus`;
                        context.commit("updateSuccessMessage", msg);
                        context.dispatch("search");
                    } else {
                        context.commit("updateErrors", response.data.errors);
                    }
                }
            } catch (e) {
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async saveDetail(context, payload) {
            try {
                payload.token = one_token();

                let response = await api.saveDetail(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                } else {
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                        total: response.data.total,
                    };

                    if (data.total !== -1) {
                        context.commit("updateErrors", []);
                        context.commit("updateAlertSuccess", true);
                        context.commit("updateDialogDetail", false);
                        var msg = `Request baru ${payload.PRDDescription} sudah tersimpan`;
                        context.commit("updateSuccessMessage", msg);
                        context.dispatch("search");
                        context.dispatch("searchDetail");
                    } else {
                        context.commit("updateErrors", response.data.errors);
                    }
                }
            } catch (e) {
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async updateDetail(context, payload) {
            try {
                payload.token = one_token();

                let response = await api.updateDetail(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                    context.commit("updateAlertError", true);
                } else {
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                        total: response.data.total,
                    };

                    if (data.total !== -1) {
                        context.commit("updateErrors", []);
                        context.commit("updateAlertSuccess", true);
                        context.commit("updateDialogDetail", false);
                        var msg = "Perubahan Request sudah tersimpan";
                        context.commit("updateSuccessMessage", msg);
                        context.dispatch("search");
                        context.dispatch("searchDetail");
                    } else {
                        context.commit("updateErrors", response.data.errors);
                    }
                }
            } catch (e) {
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async deleteDetail(context, payload) {
            try {
                payload.token = one_token();

                let response = await api.deleteDetail(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                    context.commit("updateAlertError", true);
                } else {
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                        total: response.data.total,
                    };

                    if (data.total !== -1) {
                        context.commit("updateErrors", []);
                        context.commit("updateAlertSuccess", true);
                        context.commit("updateDialogDetail", false);
                        var msg = `Request [${payload.PRDDescriptionDetail}] sudah dihapus`;
                        context.commit("updateSuccessMessage", msg);
                        context.dispatch("search");
                        context.dispatch("searchDetail");
                    } else {
                        context.commit("updateErrors", response.data.errors);
                    }
                }
            } catch (e) {
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async orderRequest(context, payload) {
            try {
                payload.token = one_token();

                let response = await api.orderRequest(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                    context.commit("updateAlertError", true);
                } else {
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                        total: response.data.total,
                    };

                    if (data.total !== -1) {
                        context.commit("updateErrors", []);
                        context.commit("updateAlertSuccess", true);
                        context.commit("updateDialogDetail", false);
                        context.dispatch("search");
                        context.commit("updateSelectedMainTable", data.records[0]);
                        context.dispatch("searchDetail");
                    } else {
                        context.commit("updateErrors", response.data.errors);
                    }
                }
            } catch (e) {
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async getVendor(context) {
            context.commit("updateLoadingAutocomplete", true)
            try {
                let payload = {}
                payload.search = context.state.selectedVendor
                payload.token = one_token()
                let response = await api.getVendor(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                    context.commit("updateAlertError", true);
                    context.commit("updateLoadingAutocomplete", false)
                } else {
                    context.commit("updateLoadingAutocomplete", false)
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                    };

                    context.commit("updateVendors", data.records);
                }
            } catch (e) {
                context.commit("updateLoadingAutocomplete", true)
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async getVendorForm(context) {
            context.commit("updateLoadingAutocomplete", true)
            try {
                let payload = {}
                payload.token = one_token()
                let response = await api.getVendorForm(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                    context.commit("updateAlertError", true);
                    context.commit("updateLoadingAutocomplete", false)
                } else {
                    context.commit("updateLoadingAutocomplete", false)
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                    };

                    context.commit("updateVendorOptions", data.records);
                }
            } catch (e) {
                context.commit("updateLoadingAutocomplete", true)
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async getItemType(context) {
            context.commit("updateLoadingAutocomplete", true)
            try {
                let payload = {}
                payload.search = context.state.selectedItemType
                payload.token = one_token()
                let response = await api.getItemType(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                    context.commit("updateAlertError", true);
                    context.commit("updateLoadingAutocomplete", false)
                } else {
                    context.commit("updateLoadingAutocomplete", false)
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                    };

                    context.commit("updateItemTypes", data.records);
                }
            } catch (e) {
                context.commit("updateLoadingAutocomplete", true)
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
        async getItemTypeForm(context) {
            context.commit("updateLoadingAutocomplete", true)
            try {
                let payload = {}
                payload.token = one_token()
                let response = await api.getItemTypeForm(payload);
                if (response.status !== "OK") {
                    context.commit("updateErrMessage", response.message);
                    context.commit("updateAlertError", true);
                    context.commit("updateLoadingAutocomplete", false)
                } else {
                    context.commit("updateLoadingAutocomplete", false)
                    context.commit("updateErrMessage", "");
                    let data = {
                        records: response.data.records,
                    };

                    context.commit("updateItemTypeOptions", data.records);
                }
            } catch (e) {
                context.commit("updateLoadingAutocomplete", true)
                context.commit("updateErrMessage", e.message);
                context.commit("updateAlertError", true);
            }
        },
    },
};
