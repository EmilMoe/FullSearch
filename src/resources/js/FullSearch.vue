<template>
    <div :class="{'full-search': true, 'open': open}">
        <div>
            <div class="full-search-close" v-show="! loadPage">
                <ui-icon-button
                        icon="close"
                        type="clear"
                        @click="close">
                </ui-icon-button>
            </div>
            <div class="search" v-show="! loadPage">
                <div>
                    <input v-model="keyword" type="text" name="keyword" placeholder="Type to search" ref="keyword">
                    <ui-progress-linear
                            show
                            :type="debounce === null ? 'determinate' : 'indeterminate'"
                            :value="100">
                    </ui-progress-linear>
                </div>
                <div>
                    <i class="material-icons">search</i>
                </div>
            </div>
            <div class="hints" v-show="! (loadPage || debounce || results.length > 0)">
                Press <strong>CTRL</strong> + <strong>SHIFT</strong> + <strong>F</strong> to open search<br>
                Press <strong>ESC</strong> to leave search
            </div>
            <div class="results">
                <div class="row">
                    <div class="col-sm-1"></div>
                    <div class="col" v-show="loadPage || (debounce && results.length === 0)">
                        <ui-progress-circular
                                show
                                color="black"
                                :stroke="2"
                                :size="140">
                        </ui-progress-circular>
                    </div>
                    <template v-for="(result, list) in results">
                        <div class="col" v-show="! loadPage ">
                            <h4>{{ result.title }}</h4>
                            <div class="row">
                                <small v-if="result.results.length === 0">No results</small>
                                <table :id="'full-search-list-'+ list" v-else>
                                    <tr
                                            v-for="(found, item) in result.results">
                                        <td
                                                @click="enter"
                                                @mouseover="setListAndItem(list, item)"
                                                :class="{hover: list ===activeList && item === activeItem}">
                                            <strong>{{ found.title }}</strong>
                                            {{ found.info }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-sm-1" v-show="! loadPage"></div>
                    </template>
                    <div class="col-sm-1" v-show="loadPage || (debounce && results.length === 0)"></div>
                </div>
            </div>
        </div>
        <div v-if="logo !== ''" class="full-search-logo">
            <img :src="logo" alt="">
        </div>
    </div>
</template>

<script>
export default {
    props: {
        logo: {
            type: String
        }
    },
    data() {
        return {
            open:       false,
            keyword:    '',
            debounce:   null,
            results:    [],
            activeList: -1,
            activeItem: -1,
            loadPage:   false,
        }
    },
    mounted() {
        document.addEventListener('keyup', event => {
            if (this.open) {
                return
            }

            if (event.ctrlKey && event.shiftKey && event.keyCode === 70) {
                this.open = true
            }
        })

        window.EventBus.$on('full-search:show', () => {
            this.open = true
        })
    },
    methods: {
        /**
         * From the current pointer, find the next list to the right that contains items.
         *
         * @return int
         */
        findNextFreeListRight() {
            for (var i = this.activeList + 1; i < this.results.length; i++) {
                if (this.results[i].results.length > 0) {
                    return i
                }
            }

            return null
        },
        /**
         * From the current pointer, find the next list to the left that contains items.
         *
         * @return int
         */
        findNextFreeListLeft() {
            for (var i = this.activeList - 1; i > -1; i--) {
                if (this.results[i].results.length > 0) {
                    return i
                }
            }

            return null
        },
        /**
         * From the current pointer at the search input, find the most appropriate list to start from.
         *
         * @return int
         */
        findAvailableListDown() {
            if (this.activeList > -1 && this.results[this.activeList].results.length > 0) {
                return this.activeList
            }

            this.activeList = -1

            return this.findNextFreeListRight()
        },
        /**
         * Reset list and item to given numbers.
         *
         * @param int list
         * @param int item
         */
        setListAndItem(list, item) {
            this.activeList = list
            this.activeItem = item
        },
        /**
         * Attach key click events.
         */
        attachMouseEvents(event) {
            if (event.keyCode === 40) {
                this.arrowDown()
            }

            if (event.keyCode === 38) {
                this.arrowUp()
            }

            if (event.keyCode === 37) {
                this.arrowLeft()
            }

            if (event.keyCode === 39) {
                this.arrowRight()
            }

            if (event.keyCode === 13) {
                this.enter()
            }

            if (event.keyCode === 27) {
                this.close()
            }
        },
        /**
         * When arrow down is clicked.
         */
        arrowDown() {
            if (this.activeItem === -1) {
                let list = this.findAvailableListDown()

                if (list === null) {
                    return
                }

                this.setListAndItem(list, 0)

                return
            }

            if (this.activeItem === this.results[this.activeList].results.length - 1) {
                return
            }

            this.activeItem++
        },
        /**
         * When arrow up is clicked.
         */
        arrowUp() {
            if (this.activeItem === -1) {
                return
            }

            if (this.activeItem === 0) {
                this.activeItem = -1
            }
            else {
                this.activeItem--
            }
        },
        /**
         * When arrow right is clicked.
         */
        arrowRight() {
            if (this.activeList === -1) {
                return
            }

            let nextList = this.findNextFreeListRight()

            if (nextList === null) {
                return
            }

            this.activeList = nextList

            if (this.results[this.activeList].results.length <= this.activeItem) {
                this.activeItem = this.results[this.activeList].results.length - 1
            }
        },
        /**
         * When arrow left is clicked.
         */
        arrowLeft() {
            if (this.activeList === 0) {
                return
            }

            let nextList = this.findNextFreeListLeft()

            if (nextList === null) {
                return
            }

            this.activeList = nextList

            if (this.results[this.activeList].results.length <= this.activeItem) {
                this.activeItem = this.results[this.activeList].results.length - 1
            }
        },
        /**
         * On key enter or mouse click, when option is selected.
         * Method will be ignored if there isn't an active list or item.
         */
        enter() {
            if (! this.activeItem || ! this.activeList) {
                return
            }

            setTimeout(() => {
                this.loadPage = true
            }, 500)

            this.$el.querySelector('.results table#full-search-list-'+ this.activeList)
                .querySelectorAll('td')[this.activeItem]
                .classList.add('active')

            window.location.href = this.results[this.activeList].results[this.activeItem].url
        },
        /**
         * Close the search.
         */
        close() {
            this.open = false
        },
        /**
         * Query server for search results.
         */
        query() {
            this.axios.get('/api/full-search?keyword='+ this.keyword).then(response => {
                if (this.debounce === null) {
                    return
                }

                this.debounce = null
                this.results  = response.data
            })
        },
        /**
         * Overrides the default browser back behaviour to close the search view.
         */
        overrideBackButton() {
            this.close()
        }
    },
    watch: {
        /**
         * When item is changed, toggle search input focus if needed.
         * When first (top) and going up, set focus, when going down, remove (blur) focus.
         *
         * @param int item
         */
        activeItem(item) {
            if (item === -1) {
                this.$refs.keyword.focus()
            }
            else {
                this.$refs.keyword.blur()
            }
        },
        /**
         * When search is open, prevent main page from retain a scroll.
         *
         * @param bool open
         */
        open(open) {
            if (open) {
                history.pushState('full-search', 'Search', '#search')

                setTimeout(() => {
                    document.querySelector('body').classList.add('no-scroll')
                }, 300)

                document.addEventListener('keyup', this.attachMouseEvents)
                document.addEventListener('popstate', this.overrideBackButton)
                this.$refs.keyword.focus()
            }
            else {
                document.querySelector('body').classList.remove('no-scroll')
                document.removeEventListener('keyup', this.attachMouseEvents)
                document.removeEventListener('popstate', this.overrideBackButton)

                setTimeout(() => {
                    this.$refs.keyword.blur()
                    this.keyword            = ''
                    clearTimeout(this.debounce)
                    this.debounce           = null
                    this.results            = []
                    this.activeList         = -1
                    this.activeItem         = -1
                }, 200)
            }
        },
        /**
         * Whenever keyword is changed, query the server but debounce it for 500ms to avoid throttle.
         * If Full Search is not open, do not initiate a search.
         *
         * @param string keyword
         */
        keyword(keyword) {
            if (! this.open) {
                return
            }

            clearTimeout(this.debounce)
            this.debounce = setTimeout(() => {
                this.query()
            }, 250)
        }
    }
}
</script>
