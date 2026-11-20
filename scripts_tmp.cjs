const fs = require('fs');
const p = 'resources/js/Pages/Admin/Dashboard.vue';
let s = fs.readFileSync(p, 'utf-8');

// 1. Header wrap
s = s.replace(`    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-end justify-between gap-4">`, `    <AdminLayout>
        <!-- Header -->
        <motion.div v-bind="staggerProps(0)">
            <div class="flex flex-wrap items-end justify-between gap-4">`);

s = s.replace(`                Manage catalogue
            </Link>
        </div>`, `                Manage catalogue
            </Link>
            </div>
        </motion.div>`);

// 2. KPI cards
s = s.replace(`            <div
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-lg border border-hairline bg-panel p-5"
            >`, `            <motion.div
                v-for="(stat, i) in stats"
                :key="stat.label"
                v-bind="staggerProps(i + 1)"
                class="rounded-lg border border-hairline bg-panel p-5"
            >`);

s = s.replace(`                <p class="mt-3 text-3xl font-semibold text-zinc-50">{{ stat.value }}</p>`, `                <p class="mt-3 text-3xl font-semibold text-zinc-50">
                    <CountUp :value="stat.value" />
                </p>`);

s = s.replace(`                    {{ stat.deltaLabel }}
                </p>
            </div>`, `                    {{ stat.deltaLabel }}
                </p>
            </motion.div>`);

// 3. Revenue chart
s = s.replace(`            <!-- Revenue chart -->
            <div class="rounded-lg border border-hairline bg-panel p-5 lg:col-span-2">`, `            <!-- Revenue chart -->
            <motion.div v-bind="staggerProps(5)" class="rounded-lg border border-hairline bg-panel p-5 lg:col-span-2">`);

s = s.replace(`                    <span>{{ revenueSeries[revenueSeries.length - 1]?.date }}</span>
                </div>
            </div>`, `                    <span>{{ revenueSeries[revenueSeries.length - 1]?.date }}</span>
                </div>
            </motion.div>`);

// 4. Donut
s = s.replace(`            <!-- Jobs by type donut -->
            <div class="rounded-lg border border-hairline bg-panel p-5">`, `            <!-- Jobs by type donut -->
            <motion.div v-bind="staggerProps(6)" class="rounded-lg border border-hairline bg-panel p-5">`);

s = s.replace(`                        </li>
                    </ul>
                </div>
            </div>
        </div>`, `                        </li>
                    </ul>
                </div>
            </motion.div>
        </div>`, 1);

// 5. Hardware nodes
s = s.replace(`            <!-- Hardware nodes -->
            <div class="rounded-lg border border-hairline bg-panel p-5 lg:col-span-2">`, `            <!-- Hardware nodes -->
            <motion.div v-bind="staggerProps(7)" class="rounded-lg border border-hairline bg-panel p-5 lg:col-span-2">`);

s = s.replace(`                                <th class="pb-2 pr-3 font-medium">Model</th>
                                <th class="pb-2 pr-3 font-medium">Status</th>
                                <th class="pb-2 pr-3 font-medium">GPU</th>`, `                                <th class="pb-2 pr-3 font-medium">Model</th>
                                <th class="pb-2 pr-3 font-medium">
                                    <LiveDot /> Status
                                </th>
                                <th class="pb-2 pr-3 font-medium">GPU</th>`);

s = s.replace(`                            <tr v-for="node in nodes" :key="node.id">`, `                            <motion.tr
                                v-for="(node, i) in nodes"
                                :key="node.id"
                                v-bind="staggerProps(i)"
                                class="transition-colors hover:bg-canvas/40"
                            >`);

s = s.replace(`                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-canvas">
                                            <div
                                                class="h-full rounded-full"
                                                :class="utilColor(node.gpu_util)"
                                                :style="{ width: node.gpu_util + '%' }"
                                            ></div>
                                        </div>`, `                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-canvas">
                                            <motion.div
                                                class="h-full rounded-full"
                                                :class="utilColor(node.gpu_util)"
                                                :initial="{ width: 0 }"
                                                :animate="{ width: node.gpu_util + '%' }"
                                                :transition="{ duration: 0.6, ease: 'easeOut', delay: 0.1 + i * 0.05 }"
                                            ></motion.div>
                                        </div>`);

s = s.replace('</td>
                        </tr>', '</td>
                        </motion.tr>');

s = s.replace(`                    </span>
                </p>
            </div>

            <!-- Recent jobs -->`, `                    </span>
                </p>
            </motion.div>

            <!-- Recent jobs -->`);

// 8. Recent jobs
s = s.replace(`            <!-- Recent jobs -->
            <div class="rounded-lg border border-hairline bg-panel p-5">`, `            <!-- Recent jobs -->
            <motion.div v-bind="staggerProps(11)" class="rounded-lg border border-hairline bg-panel p-5">`);

s = s.replace(`                        <tr v-for="job in recentJobs" :key="job.id">`, `                        <motion.tr
                            v-for="(job, i) in recentJobs"
                            :key="job.id"
                            v-bind="staggerProps(i)"
                        >`);

s = s.replace('</td>
                        </tr>', '</td>
                        </motion.tr>');

s = s.replace(`                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>`, `                    </tbody>
                </table>
            </motion.div>
        </div>
    </AdminLayout>`);

fs.writeFileSync(p, s);
console.log('motion elements:', (s.match(/<motion\./g) || []).length);
