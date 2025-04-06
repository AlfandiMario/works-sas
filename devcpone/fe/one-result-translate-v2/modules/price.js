// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/price.js"

export default {
    namespaced: true,
    state: {
        lookup_status: 0,
        searchPrice: "",
        priceHeaderList: [],
        loading: false,
        page: 1,
        totalPageHeader: 0,
        errorMsg: '',
        snackbarSuccess: false,
        snackbarError: false,
        successMsg: '',
        selectedPriceHeader: {
            "headerID": "0",
            "headerName": "",
            "headerStartDate": "",
            "headerEndDate": "",
            "headerCode": "CODE"
        },
        dialogPriceHeader: false,
        dialogDeleteHeader: false,
        startDateHeader: moment(new Date()).format('YYYY-MM-DD'),
        endDateHeader: moment(new Date()).format('YYYY-MM-DD'),
        nameHeader: "",
        filterName: "",
        filterSubGroup: [],
        filterStatus: [],
        selectedFilterSubGroup: {
            "id": "0",
            'name': 'Semua'
        },
        selectedFilterStatus: {
            "id": "A",
            'name': 'Semua'
        },
        priceTestList: [],
        priceTestPageTotal: 0,
        priceTestPage: 1,
        priceHeaderCopyList: [],
        selectedPriceHeaderCopy: {},
        dialogCopyHarga: false,
        copyPacket: false,
        dialogValidasi: false,
    },
    mutations: {
        update_lookup_status(state, val) {
            state.lookup_status = val
        },
        update_searchPrice(state, val) {
            state.searchPrice = val
        },
        update_priceHeaderList(state, val) {
            state.priceHeaderList = val
        },
        update_loading(state, val) {
            state.loading = val
        },
        update_page(state, val) {
            state.page = val
        },
        update_totalPageHeader(state, val) {
            state.totalPageHeader = val
        },
        update_errorMsg(state, val) {
            state.errorMsg = val
        },
        update_snackbarSuccess(state, val) {
            state.snackbarSuccess = val
        },
        update_snackbarError(state, val) {
            state.snackbarError = val
        },
        update_successMsg(state, val) {
            state.successMsg = val
        },
        update_selectedPriceHeader(state, val) {
            state.selectedPriceHeader = val
        },
        update_dialogPriceHeader(state, val) {
            state.dialogPriceHeader = val
        },
        update_startDateHeader(state, val) {
            state.startDateHeader = val
        },
        update_endDateHeader(state, val) {
            state.endDateHeader = val
        },
        update_nameHeader(state, val) {
            state.nameHeader = val
        },
        update_dialogDeleteHeader(state, val) {
            state.dialogDeleteHeader = val
        },
        update_filterName(state, val) {
            state.filterName = val
        },
        update_filterStatus(state, val) {
            state.filterStatus = val
        },
        update_filterSubGroup(state, val) {
            state.filterSubGroup = val
        },
        update_selectedFilterStatus(state, val) {
            state.selectedFilterStatus = val
        },
        update_selectedFilterSubGroup(state, val) {
            state.selectedFilterSubGroup = val
        },
        update_priceTestList(state, val) {
            state.priceTestList = val
        },
        update_priceTestPageTotal(state, val) {
            state.priceTestPageTotal = val
        },
        update_priceTestPage(state, val) {
            state.priceTestPage = val
        },
        update_priceHeaderCopyList(state, val) {
            state.priceHeaderCopyList = val
        },
        update_selectedPriceHeaderCopy(state, val) {
            state.selectedPriceHeaderCopy = val
        },
        update_dialogCopyHarga(state, val) {
            state.dialogCopyHarga = val
        },
        update_copyPacket(state, val) {
            state.copyPacket = val
        },
        update_dialogValidasi(state, val) {
            state.dialogValidasi = val
        },
    },
    actions: {
        async searchPriceHeader(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    token: one_token(),
                    page: context.state.page,
                    search: context.state.searchPrice
                }
                let resp = await api.search(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", false)

                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_loading", false)

                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_priceHeaderList", resp.data.records)
                    if (resp.data.records.length > 0 && context.state.selectedPriceHeader.headerID === "0") {
                        context.commit("update_selectedPriceHeader", resp.data.records[0])
                        context.dispatch("searchpricetest")
                        // this.$store.dispatch("price/searchpricetest");

                    } else if (resp.data.records.length > 0 && context.state.selectedPriceHeader.headerID !== "0") {
                        for (let i = 0; i < resp.data.records.length; i++) {
                            const e = resp.data.records[i];
                            if (e.headerID === context.state.selectedPriceHeader.headerID) {
                                context.commit("update_selectedPriceHeader", e)
                            }

                        }
                    }
                    context.commit("update_totalPageHeader", resp.data.total)
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", false)

            }
        },
        async insertPriceHeader(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    token: one_token(),
                    name: context.state.nameHeader,
                    sd: context.state.startDateHeader,
                    ed: context.state.endDateHeader
                }
                let resp = await api.insertheader(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_snackbarError", true)
                    context.commit("update_errorMsg", resp.message)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_successMsg", 'Berhasil Membuat Harga ' + context.state.nameHeader)
                    context.commit("update_loading", false)
                    context.commit("update_snackbarSuccess", true)
                    context.commit("update_dialogPriceHeader", false)
                    context.commit("update_nameHeader", '')
                    context.dispatch("searchPriceHeader")
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_snackbarError", true)
                context.commit("update_errorMsg", e)
            }
        },
        async editPriceHeader(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    id: context.state.selectedPriceHeader.headerID,
                    token: one_token(),
                    name: context.state.nameHeader,
                    sd: context.state.startDateHeader,
                    ed: context.state.endDateHeader
                }
                let resp = await api.editheader(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_snackbarError", true)
                    context.commit("update_errorMsg", resp.message)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_successMsg", 'Berhasil Edit Harga ' + context.state.nameHeader)
                    context.commit("update_loading", false)
                    context.commit("update_snackbarSuccess", true)
                    context.commit("update_dialogPriceHeader", false)
                    context.commit("update_nameHeader", '')
                    context.dispatch("searchPriceHeader")
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_snackbarError", true)
                context.commit("update_errorMsg", e)
            }
        },
        async deletePriceHeader(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    id: context.state.selectedPriceHeader.headerID,
                    token: one_token(),
                }
                let resp = await api.deleteheader(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_snackbarError", true)
                    context.commit("update_errorMsg", resp.message)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_successMsg", 'Berhasil Hapus Harga ' + context.state.nameHeader)
                    context.commit("update_loading", false)
                    context.commit("update_snackbarSuccess", true)
                    context.commit("update_dialogDeleteHeader", false)
                    context.commit("update_nameHeader", '')
                    context.dispatch("searchPriceHeader")
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_snackbarError", true)
                context.commit("update_errorMsg", e)
            }
        },
        async getpricefilter(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    token: one_token(),
                }
                let resp = await api.getfilterprice(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_loading", false)


                    context.commit("update_filterStatus", resp.data.status)
                    context.commit("update_filterSubGroup", resp.data.subgroup)
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
            }
        },
        async searchpricetest(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    token: one_token(),
                    search: context.state.filterName,
                    subgroup: context.state.selectedFilterSubGroup.id,
                    status: context.state.selectedFilterStatus.id,
                    headerid: context.state.selectedPriceHeader.headerID,
                    page: context.state.priceTestPage,
                }
                let resp = await api.searchpricetest(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_loading", false)
                    context.commit("update_priceTestList", resp.data.records)
                    context.commit("update_priceTestPageTotal", resp.data.total)

                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
            }
        },
        async savetest(context, prm) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                prm.token = one_token();
                prm.headerid = context.state.selectedPriceHeader.headerID

                let resp = await api.savetest(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_snackbarError", false)
                    context.commit("update_errorMsg", resp.message)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_successMsg", 'Berhasil simpan harga')
                    context.commit("update_snackbarSuccess", false)
                    context.commit("update_loading", false)
                    context.dispatch("searchpricetest");
                    context.dispatch("searchPriceHeader");

                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
            }
        },
        async copyharga(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    token: one_token(),
                    headerid: context.state.selectedPriceHeaderCopy.headerID,
                    name: context.state.nameHeader,
                    copypacket: context.state.copyPacket
                }

                let resp = await api.copyharga(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", false)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_loading", false)
                    context.commit("update_successMsg", 'Berhasil copy harga')
                    context.commit("update_snackbarSuccess", false)
                    context.dispatch("searchPriceHeader");
                    context.commit("update_dialogCopyHarga", false)
                    context.commit("update_nameHeader", '')
                    context.commit("update_selectedPriceHeaderCopy", {})
                    context.commit("update_copyPacket", false)

                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
            }
        },
        async validateheader(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    token: one_token(),
                    id: context.state.selectedPriceHeader.headerID,
                }

                let resp = await api.validateheader(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_successMsg", 'Berhasil validasi price header')
                    context.commit("update_snackbarSuccess", false)

                    context.commit("update_loading", false)
                    context.commit("update_dialogValidasi", false)
                    context.dispatch("searchPriceHeader");
                    context.dispatch("searchpricetest");
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
            }
        },
        async searchPriceHeaderAutocomplete(context, prm) {
            context.commit("update_lookup_status", 1)
            try {
                prm.token = one_token();
                prm.headerid = context.state.selectedPriceHeader.headerID

                let resp = await api.searchpricetestautocomplete(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)

                    context.commit("update_errorMsg", resp.message)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_priceHeaderCopyList", resp.data.records)

                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)

                context.commit("update_errorMsg", e)
            }
        },
    }
}
