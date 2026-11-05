<x-app-layout>
    <x-slot name="title">Kelola Peminjaman</x-slot>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Peminjaman Kendaraan') }}
        </h2>
    </x-slot>
    <script>
        // Simpan data master checklist ke window global agar tidak merusak atribut x-data HTML
        window.masterChecklistsData = {!! json_encode($masterChecklists ?? []) !!};
    </script>

    <div class="pt-2 pb-6" 
        x-data="{ 
            search: '', 
            sort: 'upcoming',
            bookings: {{ Illuminate\Support\Js::from($bookings->items()) }}, 
            selectedBooking: null,
            activeBooking: null,
            isLoading: false,

            vehicles: {{ Illuminate\Support\Js::from($availableVehicles) }},
            drivers: {{ Illuminate\Support\Js::from($availableDrivers) }},
            activeBookings: {{ Illuminate\Support\Js::from($activeBookings) }},
            showReviewModal: false,
            reviewForm: {
                action: 'approve',
                vehicle_id: '',
                driver_id: '',
                admin_note: '',
                signaturePad: null,
                signatureData: '',
                signatureError: '',
                existingSignatureUrl: null
            },

            // HELPER: Hak Akses Operasional Perjalanan
            canOperateTrip(booking) {
                if (!booking) return false;
                const userId = {{ Auth::id() }};
                const userRole = '{{ Auth::user()->role }}';

                if (['admin_lap', 'admin_app'].includes(userRole)) return true;
                if (booking.driver_id == userId) return true;

                const participants = Array.isArray(booking.participants) ? booking.participants : [];
                return participants.includes(userId) || participants.includes(String(userId));
            },

            // HELPER: Filter Armada yang Tidak Bentrok
            getAvailableVehicles() {
                if (!this.selectedBooking) return this.vehicles;

                const reqStart = new Date(this.selectedBooking.start_date);
                const reqEnd   = new Date(this.selectedBooking.end_date);
                const currentBookingId = this.selectedBooking.id;

                return this.vehicles.filter(vehicle => {
                    const isConflict = this.activeBookings.some(active => {
                        if (active.id === currentBookingId) return false;
                        if (active.vehicle_id !== vehicle.id) return false;

                        const actStart = new Date(active.start_date);
                        const actEnd   = new Date(active.end_date);

                        return (reqStart <= actEnd && reqEnd >= actStart);
                    });

                    return !isConflict;
                });
            },

            // HELPER: Filter Driver yang Tidak Bentrok
            getAvailableDrivers() {
                if (!this.selectedBooking) return this.drivers;

                const reqStart = new Date(this.selectedBooking.start_date);
                const reqEnd   = new Date(this.selectedBooking.end_date);
                const currentBookingId = this.selectedBooking.id;

                const freeDrivers = this.drivers.filter(driver => {
                    if (driver.role !== 'driver') return false;

                    return !this.activeBookings.some(active => {
                        if (active.id === currentBookingId) return false;
                        if (active.driver_id !== driver.id) return false;

                        const actStart = new Date(active.start_date);
                        const actEnd   = new Date(active.end_date);

                        return (reqStart <= actEnd && reqEnd >= actStart);
                    });
                });
                const participantsList = this.selectedBooking.participants_users || [];
                return {
                    primaryDrivers: freeDrivers,
                    participants: participantsList
                };
            },

            masterChecklists: window.masterChecklistsData || [],

            // Helper Generate Snapshot Items Awal
            getInitialChecklistItems() {
                if (!Array.isArray(this.masterChecklists) || this.masterChecklists.length === 0) {
                    return [];
                }
                return this.masterChecklists.map(item => ({
                    id: item.id,
                    name: item.name,
                    category: item.category,
                    is_checked: false // Driver wajib mencentang secara manual
                }));
            },

            checkDynamicIncidentStatus(formState) {
                const items = formState.items || [];
                
                // Mengecek apakah ada item 'condition' ATAU 'completeness' yang belum/tidak dicentang
                const hasUncheckedItem = items.some(
                    item => ['condition', 'completeness'].includes(item.category) && !item.is_checked
                );
                
                formState.hasIncident = hasUncheckedItem;
            },

            openReviewModal(booking) {
                this.selectedBooking = booking;

                // 1. Logika Lama: Mapping Anggota Tim Permohonan ke Driver Options
                if (booking.participants && Array.isArray(booking.participants)) {
                    this.selectedBooking.participants_users = (this.drivers || []).filter(d => 
                        booking.participants.includes(d.id) || booking.participants.includes(String(d.id))
                    );
                } else {
                    this.selectedBooking.participants_users = [];
                }

                // 2. Reset & Set State Form Review
                this.reviewForm.action = 'approve';
                this.reviewForm.vehicle_id = booking.vehicle_id ? String(booking.vehicle_id) : '';
                this.reviewForm.driver_id = booking.driver_id ? String(booking.driver_id) : '';
                this.reviewForm.admin_note = booking.admin_note || '';
                this.reviewForm.signatureError = '';
                this.reviewForm.signatureData = '';

                // 3. Cek TTD Lama Admin Lapangan (Untuk Penanganan Re-Assign)
                const adminSignature = (booking.signatures || []).find(s => 
                    s.signer_role === 'admin_lap' && s.document_type === 'bast_peminjaman'
                );
                
                if (adminSignature && adminSignature.signature_path) {
                    this.reviewForm.existingSignatureUrl = `/storage/${adminSignature.signature_path}`;
                } else {
                    this.reviewForm.existingSignatureUrl = null;
                }

                // 4. Buka Modal & Inisialisasi Canvas TTD
                this.showReviewModal = true;
                this.initReviewSignaturePad();
            },

            initReviewSignaturePad() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const canvas = this.$refs.reviewSignatureCanvas;
                        if (!canvas) return;

                        const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                        const height = 144; // h-36

                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = width * ratio;
                        canvas.height = height * ratio;

                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);

                        const SignaturePadClass = window.SignaturePad || SignaturePad;

                        if (!this.reviewForm.signaturePad) {
                            this.reviewForm.signaturePad = new SignaturePadClass(canvas, {
                                backgroundColor: 'rgb(255, 255, 255)',
                                penColor: 'rgb(0, 0, 128)',
                                minWidth: 1.5,
                                maxWidth: 3.5
                            });
                        } else {
                            this.reviewForm.signaturePad.clear();
                        }
                    }, 150);
                });
            },

            clearReviewSignature() {
                if (this.reviewForm.signaturePad) {
                    this.reviewForm.signaturePad.clear();
                    this.reviewForm.signatureError = '';
                    this.reviewForm.signatureData = '';
                }
            },

            submitReviewForm() {
                if (this.reviewForm.action === 'approve') {
                    const isCanvasEmpty = !this.reviewForm.signaturePad || this.reviewForm.signaturePad.isEmpty();
                    
                    // Jika tidak ada TTD lama DAN canvas kosong -> Tampilkan Error
                    if (!this.reviewForm.existingSignatureUrl && isCanvasEmpty) {
                        this.reviewForm.signatureError = 'Tanda tangan Admin Lapangan wajib dibubuhkan untuk menyetujui!';
                        return;
                    }

                    // Jika canvas terisi (baik permohonan baru maupun overwrite saat re-assign)
                    if (!isCanvasEmpty) {
                        this.reviewForm.signatureData = this.reviewForm.signaturePad.toDataURL('image/png');
                    }
                    
                    this.reviewForm.signatureError = '';
                } else {
                    this.reviewForm.signatureError = '';
                    this.reviewForm.signatureData = '';
                }

                this.$nextTick(() => {
                    this.$refs.reviewFormElement.submit();
                });
            },

            showApproverModal: false,
            approverActionType: '', 
            approverForm: {
                action: 'approve',
                admin_note: '',
                signaturePad: null,
                signatureData: '',
                signatureError: '',
                existingSignatureUrl: null
            },

            openApproverModal(booking, type) {
                this.selectedBooking = booking;
                this.approverActionType = type;
                this.approverForm.action = 'approve';
                this.approverForm.admin_note = '';
                this.approverForm.signatureError = '';
                this.approverForm.signatureData = '';

                const activeLogId = booking.log?.id;
                const allChecklists = booking.checklists || [];

                if (type === 'waiting') {
                    const vehicleChecklists = booking.vehicle?.checklists || [];
                    const lastEndChecklist = vehicleChecklists.filter(c => c.type === 'end').slice(-1)[0];
                    const fallbackChecklist = vehicleChecklists.slice(-1)[0];

                    this.selectedBooking.displayChecklists = lastEndChecklist 
                        ? [lastEndChecklist] 
                        : (fallbackChecklist ? [fallbackChecklist] : []);
                } else {
                    const startChecklist = allChecklists.find(c => c.type === 'start' && c.vehicle_log_id === activeLogId) 
                        || allChecklists.filter(c => c.type === 'start').slice(-1)[0];
                        
                    const endChecklist = allChecklists.find(c => c.type === 'end' && c.vehicle_log_id === activeLogId) 
                        || allChecklists.filter(c => c.type === 'end').slice(-1)[0];

                    const displayList = [];
                    if (startChecklist) displayList.push(startChecklist);
                    if (endChecklist) displayList.push(endChecklist);

                    this.selectedBooking.displayChecklists = displayList;
                }

                // Extract Teks Catatan
                const condNotes = booking.log?.condition_notes || {};
                this.selectedBooking.startNotes = typeof condNotes === 'object' ? condNotes.start : null;
                this.selectedBooking.endNotes   = typeof condNotes === 'object' ? condNotes.end : null;

                // Extract Foto Insiden
                this.selectedBooking.parsedStartPhotos = [];
                this.selectedBooking.parsedEndPhotos   = [];

                if (booking.log && booking.log.condition_photos) {
                    let rawPhotos = booking.log.condition_photos;
                    if (typeof rawPhotos === 'string') {
                        try { rawPhotos = JSON.parse(rawPhotos); } catch (e) { rawPhotos = {}; }
                    }
                    
                    this.selectedBooking.parsedStartPhotos = Array.isArray(rawPhotos?.start) ? rawPhotos.start : [];
                    this.selectedBooking.parsedEndPhotos   = Array.isArray(rawPhotos?.end) ? rawPhotos.end : [];
                }

                const targetDocType = (type === 'waiting') ? 'bast_peminjaman' : 'bast_pengembalian';
                const existingSig = (booking.signatures || []).find(s => 
                    s.signer_role === 'approver' && s.document_type === targetDocType
                );

                this.approverForm.existingSignatureUrl = existingSig?.signature_path ? `/storage/${existingSig.signature_path}` : null;

                this.showApproverModal = true;
                this.initApproverSignaturePad();
            },

            initApproverSignaturePad() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const canvas = this.$refs.approverSignatureCanvas;
                        if (!canvas) return;

                        const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                        const height = 144; // setara h-36

                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = width * ratio;
                        canvas.height = height * ratio;

                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);

                        const SignaturePadClass = window.SignaturePad || SignaturePad;

                        if (!this.approverForm.signaturePad) {
                            this.approverForm.signaturePad = new SignaturePadClass(canvas, {
                                backgroundColor: 'rgb(255, 255, 255)', // Putih Padat
                                penColor: 'rgb(0, 0, 128)',             // Biru Navy
                                minWidth: 1.5,
                                maxWidth: 3.5
                            });
                        } else {
                            this.approverForm.signaturePad.clear();
                        }
                    }, 150);
                });
            },

            clearApproverSignature() {
                if (this.approverForm.signaturePad) {
                    this.approverForm.signaturePad.clear();
                    this.approverForm.signatureError = '';
                    this.approverForm.signatureData = '';
                }
            },

            submitApproverReviewForm() {
                if (this.approverForm.action === 'approve') {
                    const isCanvasEmpty = !this.approverForm.signaturePad || this.approverForm.signaturePad.isEmpty();
                    
                    // Validasi wajib TTD jika tidak ada TTD tersimpan DAN canvas kosong
                    if (!this.approverForm.existingSignatureUrl && isCanvasEmpty) {
                        this.approverForm.signatureError = 'Tanda tangan pengesahan Approver wajib dibubuhkan untuk menyetujui!';
                        return;
                    }

                    // Ambil data gambar dari canvas jika canvas dicoret
                    if (!isCanvasEmpty) {
                        this.approverForm.signatureData = this.approverForm.signaturePad.toDataURL('image/png');
                    }
                    
                    this.approverForm.signatureError = '';
                } else {
                    // Jika menolak (reject), kososngkan error & data TTD
                    this.approverForm.signatureError = '';
                    this.approverForm.signatureData = '';
                }

                this.$nextTick(() => {
                    this.$refs.approverFormElement.submit();
                });
            },

            showRouteModal: false,
            routeBookingDetail: null,
            openRouteDetailModal(booking) {
                this.routeBookingDetail = booking;
                this.showRouteModal = true;
            },
            getGoogleMapsUrl(destination) {
                if (!destination) return '#';
                return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(destination)}`;
            },
            getWaUrl(phone) {
                if (!phone) return '#';
                let cleanPhone = phone.replace(/[^0-9]/g, '');
                if (cleanPhone.startsWith('0')) {
                    cleanPhone = '62' + cleanPhone.slice(1);
                }
                return `https://wa.me/${cleanPhone}`;
            },

            // --- MODAL START TRIP (PRE-TRIP CHECKLIST) ---
            showStartTripModal: false,
            startTripForm: {
                letter_slug: '',
                start_fuel_level: 'Full',
                items: [],
                hasIncident: false,
                notes: ''
            },

            openStartTripModal(booking) {
                this.selectedBooking = booking;
                this.startTripForm.letter_slug = booking.letter_slug;
                this.startTripForm.start_fuel_level = 'Full';
                
                // Inisialisasi Dynamic Items (Default false)
                this.startTripForm.items = this.getInitialChecklistItems();
                
                // Set awal hasIncident TRUE agar user wajib centang semua item sebelum start trip
                this.startTripForm.hasIncident = true; 
                this.startTripForm.notes = '';
                
                this.showStartTripModal = true;
            },

            // --- MODAL COMPLETE TRIP (POST-TRIP CHECKLIST & CUCI MOBIL) ---
            showCompleteModal: false,
            completeTripForm: {
                letter_slug: '',
                end_fuel_level: 'Full',
                is_multi_day: false,
                is_washed: false,
                return_letter_number: '', 
                hasIncident: true,  
                condition_notes: '',
                items: [],
                signaturePad: null,
                signatureData: '',
                signatureError: '',
                existingSignatureUrl: null // Ditambahkan untuk menyimpan URL preview TTD lama
            },

            openCompleteModal(booking) {
                this.selectedBooking = booking;
                this.completeTripForm.letter_slug = booking.letter_slug;
                this.completeTripForm.end_fuel_level = 'Full';
                
                // Hitung selisih HARI KALENDER saja (Abaikan Jam)
                if (booking.start_date && booking.end_date) {
                    const startDateStr = booking.start_date.substring(0, 10);
                    const endDateStr = booking.end_date.substring(0, 10);
                    
                    const start = new Date(startDateStr);
                    const end = new Date(endDateStr);
                    
                    const diffTime = end.getTime() - start.getTime();
                    const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
                    
                    this.completeTripForm.is_multi_day = diffDays >= 1;
                } else {
                    this.completeTripForm.is_multi_day = false;
                }

                this.completeTripForm.is_washed = false;
                this.completeTripForm.return_letter_number = '';
                this.completeTripForm.items = this.getInitialChecklistItems();
                this.completeTripForm.hasIncident = true;
                this.completeTripForm.condition_notes = '';

                // Reset Signature State & Form Errors
                this.completeTripForm.signatureError = '';
                this.completeTripForm.signatureData = '';
                
                // Cek apakah pemohon sudah memiliki TTD untuk BAST Pengembalian
                const existingSig = (booking.signatures || []).find(s => 
                    s.signer_role === 'pemohon' && s.document_type === 'bast_pengembalian'
                );

                if (existingSig && existingSig.signature_path) {
                    this.completeTripForm.existingSignatureUrl = `/storage/${existingSig.signature_path}`;
                } else {
                    this.completeTripForm.existingSignatureUrl = null;
                }

                this.showCompleteModal = true;
                this.initCompleteSignaturePad();
            },

            initCompleteSignaturePad() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const canvas = this.$refs.completeSignatureCanvas;
                        if (!canvas) return;

                        const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                        const height = 144;

                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = width * ratio;
                        canvas.height = height * ratio;

                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);

                        const SignaturePadClass = window.SignaturePad || SignaturePad;

                        if (!this.completeTripForm.signaturePad) {
                            this.completeTripForm.signaturePad = new SignaturePadClass(canvas, {
                                backgroundColor: 'rgb(255, 255, 255)',
                                penColor: 'rgb(0, 0, 128)',
                                minWidth: 1.5,
                                maxWidth: 3.5
                            });
                        } else {
                            this.completeTripForm.signaturePad.clear();
                        }
                    }, 150);
                });
            },

            clearCompleteSignature() {
                if (this.completeTripForm.signaturePad) {
                    this.completeTripForm.signaturePad.clear();
                    this.completeTripForm.signatureError = '';
                    this.completeTripForm.signatureData = '';
                }
            },

            submitCompleteTripForm() {
                const isPadEmpty = !this.completeTripForm.signaturePad || this.completeTripForm.signaturePad.isEmpty();
                
                // Jika canvas kosong DAN belum ada TTD yang tersimpan sebelumnya
                if (isPadEmpty && !this.completeTripForm.existingSignatureUrl) {
                    this.completeTripForm.signatureError = 'Tanda tangan pengesahan pengembalian wajib dibubuhkan!';
                    return;
                }

                this.completeTripForm.signatureError = '';

                // Hanya ambil data URL jika canvas tidak kosong (user membuat coretan baru)
                if (!isPadEmpty) {
                    this.completeTripForm.signatureData = this.completeTripForm.signaturePad.toDataURL('image/png');
                } else {
                    this.completeTripForm.signatureData = '';
                }

                this.$nextTick(() => {
                    this.$refs.completeTripFormElement.submit();
                });
            },

            // Verify Return Form State & Methods
            showVerifyReturnModal: false,
            verifyReturnForm: {
                items: [],
                is_washed: false,
                wash_receipt_photo: null,
                admin_verification_notes: '',
                signaturePad: null,
                signatureData: '',
                signatureError: '',
                existingSignatureUrl: null
            },

            openVerifyReturnModal(booking) {
                this.selectedBooking = booking;
                
                const endChk = (booking.checklists || []).find(c => c.type === 'end') || {};
                
                // Jika driver sudah memasukkan items snapshot, gunakan itu. Jika belum, gunakan initial master.
                if (endChk.items && Array.isArray(endChk.items) && endChk.items.length > 0) {
                    this.verifyReturnForm.items = JSON.parse(JSON.stringify(endChk.items));
                } else {
                    this.verifyReturnForm.items = this.getInitialChecklistItems();
                }
                
                this.verifyReturnForm.is_washed = Boolean(endChk.is_washed);
                this.verifyReturnForm.wash_receipt_photo = endChk.wash_receipt_photo || null;
                
                this.verifyReturnForm.admin_verification_notes = booking.log?.admin_verification_notes || '';
                
                this.verifyReturnForm.signatureError = '';
                this.verifyReturnForm.signatureData = '';

                // Cek TTD Lama Admin Lapangan pada BAST Pengembalian
                const existingSig = (booking.signatures || []).find(s => 
                    s.signer_role === 'admin_lap' && s.document_type === 'bast_pengembalian'
                );

                if (existingSig && existingSig.signature_path) {
                    this.verifyReturnForm.existingSignatureUrl = `/storage/${existingSig.signature_path}`;
                } else {
                    this.verifyReturnForm.existingSignatureUrl = null;
                }

                this.showVerifyReturnModal = true;
                this.initVerifyReturnSignaturePad();
            },

            initVerifyReturnSignaturePad() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const canvas = this.$refs.verifyReturnSignatureCanvas;
                        if (!canvas) return;

                        const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                        const height = 144;

                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = width * ratio;
                        canvas.height = height * ratio;

                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);

                        const SignaturePadClass = window.SignaturePad || SignaturePad;

                        if (!this.verifyReturnForm.signaturePad) {
                            this.verifyReturnForm.signaturePad = new SignaturePadClass(canvas, {
                                backgroundColor: 'rgb(255, 255, 255)',
                                penColor: 'rgb(0, 0, 128)',
                                minWidth: 1.5,
                                maxWidth: 3.5
                            });
                        } else {
                            this.verifyReturnForm.signaturePad.clear();
                        }
                    }, 150);
                });
            },

            clearVerifyReturnSignature() {
                if (this.verifyReturnForm.signaturePad) {
                    this.verifyReturnForm.signaturePad.clear();
                    this.verifyReturnForm.signatureError = '';
                    this.verifyReturnForm.signatureData = '';
                }
            },

            submitVerifyReturnForm() {
                const isCanvasEmpty = !this.verifyReturnForm.signaturePad || this.verifyReturnForm.signaturePad.isEmpty();
                
                if (!this.verifyReturnForm.existingSignatureUrl && isCanvasEmpty) {
                    this.verifyReturnForm.signatureError = 'Tanda tangan verifikasi Admin Lapangan wajib dibubuhkan!';
                    return;
                }

                if (!isCanvasEmpty) {
                    this.verifyReturnForm.signatureData = this.verifyReturnForm.signaturePad.toDataURL('image/png');
                }

                this.verifyReturnForm.signatureError = '';

                this.$nextTick(() => {
                    this.$refs.verifyReturnFormElement.submit();
                });
            },

            showCancelBookingModal: false,
            cancelBookingForm: {
                letter_slug: '',
                letter_number: ''
            },
            openCancelBookingModal(booking) {
                this.cancelBookingForm.letter_slug = booking.letter_slug;
                this.cancelBookingForm.letter_number = booking.letter_number;
                this.showCancelBookingModal = true;
            },

            showCancelApprovalModal: false,
            cancelApprovalForm: {
                letter_slug: '',
                letter_number: '',
                vehicle_name: '',
                cancel_reason: ''
            },
            openCancelApprovalModal(booking) {
                this.cancelApprovalForm.letter_slug = booking.letter_slug;
                this.cancelApprovalForm.letter_number = booking.letter_number;
                this.cancelApprovalForm.vehicle_name = booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model} (${booking.vehicle.plate_number})` : 'Armada Operasional';
                this.cancelApprovalForm.cancel_reason = '';
                this.showCancelApprovalModal = true;
            },

            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            fetchBookings() {
                this.isLoading = true;
                fetch(`/api/search/bookings?q=${encodeURIComponent(this.search)}&sort=${this.sort}`)
                    .then(res => res.json())
                    .then(data => {
                        this.bookings = data.data;
                        this.isLoading = false;
                    })
                    .catch(() => { this.isLoading = false; });
            }
        }"
        x-init="$watch('search', value => fetchBookings());$watch('sort', value => fetchBookings())">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            
            {{-- AREA SEARCH & FILTER (STACK MOBILE, INLINE DESKTOP) --}}
            <div class="bg-white rounded-2xl p-4 border border-gray-200/80 shadow-xs flex flex-col md:flex-row justify-between items-center gap-3">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:max-w-xl">
                    <!-- INPUT SEARCH -->
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none z-10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>

                        <input type="text" 
                            x-model="search" 
                            @input.debounce.300ms="fetchBookings()" 
                            placeholder="Cari No Surat, Pemohon, Mobil..." 
                            class="pl-10 pr-10 py-2.5 w-full text-xs font-medium text-gray-900 bg-gray-50/50 border border-gray-300 rounded-xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 hover:border-gray-400 shadow-2xs transition-all duration-200">
                        
                        <!-- Loading Spinner -->
                        <div x-show="isLoading" class="absolute inset-y-0 right-0 pr-3 flex items-center z-10" x-cloak>
                            <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- SELECT SORT (CLEAN FLOWBITE STYLE) -->
                    <div class="relative w-full sm:w-auto shrink-0">
                        <select x-model="sort" 
                            @change="fetchBookings()" 
                            aria-label="Urutkan Peminjaman"
                            class="appearance-none w-full sm:w-auto pl-4 pr-10 py-2.5 text-xs font-semibold text-gray-700 bg-gray-50/50 hover:bg-gray-100/70 border border-gray-300 rounded-xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 hover:border-gray-400 shadow-2xs transition-all duration-200 cursor-pointer">
                        <option value="created_latest">Urutkan: Terbaru</option>
                        <option value="upcoming">Urutkan: Jadwal Terdekat</option>
                        <option value="start_desc">Urutkan: Jadwal Terjauh</option>
                    </select>
                    </div>
                </div>

                <a href="{{ route('bookings.create') }}" class="w-full md:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md transition shrink-0 inline-flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    <span>Ajukan Peminjaman</span>
                </a>
            </div>

            {{-- 1. TAMPILAN KARTU SMARTPHONE (block md:hidden) --}}
            <div class="block md:hidden space-y-3">
                <template x-for="booking in bookings" :key="booking.id">
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-xs space-y-3">
                        <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2.5">
                            <div>
                                <a :href="`/bookings/${booking.letter_slug}`" class="font-mono font-bold text-sm text-indigo-600 hover:underline block" x-text="booking.letter_number"></a>
                                <p class="text-xs text-gray-500 font-medium mt-0.5" x-text="booking.user ? booking.user.name : '-'"></p>
                            </div>
                            <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-full shrink-0 uppercase tracking-wider"
                                  :class="{
                                      'bg-yellow-100 text-yellow-800': booking.status === 'pending',
                                      'bg-amber-100 text-amber-800': booking.status === 'waiting',
                                      'bg-blue-100 text-blue-800': booking.status === 'approved',
                                      'bg-purple-100 text-purple-800': booking.status === 'on_trip',
                                      'bg-indigo-100 text-indigo-800': booking.status === 'returned',
                                      'bg-cyan-100 text-cyan-800': booking.status === 'checked',
                                      'bg-emerald-100 text-emerald-800': booking.status === 'completed',
                                      'bg-gray-200 text-gray-700': booking.status === 'canceled',
                                      'bg-rose-100 text-rose-800': booking.status === 'rejected'
                                  }"
                                  x-text="booking.status.replace('_', ' ')">
                            </span>
                        </div>

                        <div class="space-y-1.5 text-xs text-gray-700">
                            <div class="flex items-center gap-2 font-bold text-sm text-gray-900">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M6 2a2 2 0 0 0-2 2v15a3 3 0 0 0 3 3h12a1 1 0 1 0 0-2h-2v-2h2a1 1 0 0 0 1-1V4a2 2 0 0 0-2-2h-8v16h5v2H7a1 1 0 1 1 0-2h1V2H6Z" clip-rule="evenodd"/>
                                </svg>
                                <span x-text="booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model} (${booking.vehicle.plate_number})` : 'Belum Ada Armada'"></span>
                            </div>

                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <span x-text="formatDate(booking.start_date)"></span> 
                                    <span class="font-bold text-gray-600">s.d.</span> 
                                    <span x-text="formatDate(booking.end_date)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- AKSI UTAMA MOBILE --}}
                        <div class="pt-2 border-t border-gray-100 flex items-center justify-between gap-2" x-data="{ menuOpen: false }">
                            <div class="flex-1 flex items-center gap-1.5">
                                {{-- PERBAIKI PERMOHONAN DITOLAK (PEMOHON) --}}
                                <template x-if="booking.status === 'rejected' && booking.user_id === {{ Auth::id() }}">
                                    <a :href="`/bookings/${booking.letter_slug}/edit`" 
                                    class="w-full py-2.5 px-4 bg-transparent border border-amber-600 text-amber-600 hover:bg-amber-600/10 active:bg-amber-600/20 font-semibold rounded-xl text-xs shadow-xs text-center inline-flex items-center justify-center gap-2 transition-colors duration-150 ease-in-out">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                        </svg>
                                        <span>Perbaiki Permohonan</span>
                                    </a>
                                </template>

                                {{-- MULAI PERJALANAN (DRIVER / ADMIN LAPANGAN / ANGGOTA TIM) --}}
                                <template x-if="booking.status === 'approved' && canOperateTrip(booking)">
                                    <button type="button" 
                                            @click="openStartTripModal(booking)" 
                                            class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
                                            <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/>
                                        </svg>
                                        <span>Mulai Perjalanan</span>
                                    </button>
                                </template>

                                {{-- PERJALANAN SELESAI (DRIVER / ADMIN LAPANGAN / ANGGOTA TIM) --}}
                                <template x-if="booking.status === 'on_trip' && canOperateTrip(booking)">
                                    <button type="button" 
                                            @click="openCompleteModal(booking)" 
                                            class="w-full py-1.5 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-purple-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                            <polyline points="22 4 12 14.01 9 11.01"/>
                                        </svg>
                                        <span>Perjalanan Selesai</span>
                                    </button>
                                </template>

                                {{-- AKSI ADMIN LAPANGAN --}}
                                @if(in_array(Auth::user()->role, ['admin_lap', 'admin_app']))
                                    <template x-if="booking.status === 'pending'">
                                        <button type="button" 
                                                @click="openReviewModal(booking);" 
                                                class="w-full py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/30 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current text-indigo-600 shrink-0" viewBox="0 0 24 24">
                                                <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                            </svg>
                                            <span>Tinjau & Plot Armada</span>
                                        </button>
                                    </template>
                                    <template x-if="booking.status === 'returned'">
                                        <button type="button" 
                                                @click="openVerifyReturnModal(booking)" 
                                                class="w-full py-1.5 bg-cyan-600 hover:bg-cyan-700 active:bg-cyan-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-cyan-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                                <path d="m9 14 2 2 4-4"/>
                                            </svg>
                                            <span>Tinjau Kepulangan</span>
                                        </button>
                                    </template>
                                @endif

                                {{-- AKSI APPROVER --}}
                                @if(Auth::user()->role === 'approver')
                                    <template x-if="booking.status === 'waiting'">
                                        <button type="button" 
                                                @click="openApproverModal(booking, 'waiting')" 
                                                class="w-full py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                                <path d="m9 14 2 2 4-4"/>
                                            </svg>
                                            <span>Tinjau & Setujui</span>
                                        </button>
                                    </template>
                                    <template x-if="booking.status === 'checked'">
                                        <button type="button" 
                                                @click="openApproverModal(booking, 'checked')" 
                                                class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                                <path d="m9 15 2 2 4-4"/>
                                            </svg>
                                            <span>Sahkan Pengembalian</span>
                                        </button>
                                    </template>
                                @endif
                            </div>

                            {{-- MENU TITIK TIGA MOBILE --}}
                            <div class="relative shrink-0">
                                <button @click="menuOpen = !menuOpen" @click.away="menuOpen = false" type="button" class="p-2 text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl focus:outline-none transition" aria-label="Menu Opsi Aksi Mobile">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                    </svg>
                                </button>

                                {{-- DROPDOWN MENU TITIK TIGA MOBILE --}}
                                <div x-show="menuOpen" x-cloak class="absolute right-0 bottom-full mb-1 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 z-30 text-left text-xs space-y-0.5">
                                    {{-- 1. UBAH / EDIT PERMOHONAN (KHUSUS PEMOHON & STATUS PENDING) --}}
                                    <template x-if="booking.user_id === {{ Auth::id() }} && booking.status === 'pending'">
                                        <a :href="`/bookings/${booking.letter_slug}/edit`" class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-amber-50 hover:text-amber-600 font-medium transition">
                                            <!-- Icon Edit/Pencil Amber -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 20h9"/>
                                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                            </svg>
                                            <span>Edit Permohonan</span>
                                        </a>
                                    </template>

                                    {{-- 2. BATALKAN PENGAJUAN (PEMOHON) --}}
                                    <template x-if="booking.user_id === {{ Auth::id() }} && ['pending', 'waiting', 'approved'].includes(booking.status)">
                                        <button type="button" @click="openCancelBookingModal(booking); menuOpen = false;" class="w-full flex items-center gap-2 px-4 py-2 text-amber-600 hover:bg-amber-50 font-bold transition text-left">
                                            <!-- Icon Ban/Cancel Amber -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"/>
                                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                            </svg>
                                            <span>Batalkan Pengajuan</span>
                                        </button>
                                    </template>

                                    {{-- 3. RE-ASSIGN & BATAL PERSETUJUAN (ADMIN LAPANGAN / APP) --}}
                                    @if(in_array(Auth::user()->role, ['admin_lap', 'admin_app']))
                                        <template x-if="booking.status === 'waiting'">
                                            <div class="space-y-0.5 border-t border-gray-100 pt-1 mt-1">
                                                <button type="button" @click="openReviewModal(booking); menuOpen = false;" class="w-full flex items-center gap-2 px-4 py-2 text-indigo-600 hover:bg-indigo-50 font-medium transition text-left">
                                                    <!-- Icon Re-assign / Refresh Indigo -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                                        <path d="M3 3v5h5"/>
                                                        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                                                        <path d="M16 16h5v5"/>
                                                    </svg>
                                                    <span>Re-assign Armada</span>
                                                </button>
                                                <button type="button" @click="openCancelApprovalModal(booking); menuOpen = false;" class="w-full flex items-center gap-2 px-4 py-2 text-rose-600 hover:bg-rose-50 font-bold transition text-left">
                                                    <!-- Icon X Circle Rose -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"/>
                                                        <line x1="15" y1="9" x2="9" y2="15"/>
                                                        <line x1="9" y1="9" x2="15" y2="15"/>
                                                    </svg>
                                                    <span>Batalkan Persetujuan</span>
                                                </button>
                                            </div>
                                        </template>
                                    @endif

                                    {{-- 4. DETAIL RUTE & TIM --}}
                                    <button type="button" @click="openRouteDetailModal(booking); menuOpen = false;" class="w-full flex items-center gap-2 px-4 py-2 text-indigo-600 hover:bg-indigo-50 font-medium transition text-left border-t border-gray-100 mt-1 pt-1">
                                        <!-- Icon Map / Route Indigo -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="1 6 1 22 8 18 15 22 23 18 23 2 15 6 8 2 1 6"/>
                                            <line x1="8" y1="2" x2="8" y2="18"/>
                                            <line x1="15" y1="6" x2="15" y2="22"/>
                                        </svg>
                                        <span>Detail Rute & Tim</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- 2. TAMPILAN TABEL DESKTOP (hidden md:block) --}}
            <div class="hidden md:block bg-white rounded-2xl border border-gray-200/80 shadow-xs">
                {{-- Tambahkan pb-28 dan min-h-[320px] agar ada ruang untuk dropdown --}}
                <div class="overflow-x-auto pb-28 min-h-[320px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 text-xs font-semibold text-gray-600 uppercase">
                                <th class="p-3">No Surat Tugas</th>
                                <th class="p-3">Peminjam</th>
                                <th class="p-3">Mobil</th>
                                <th class="p-3">Jadwal</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-center">Aksi Operasional</th>
                                <th class="p-3 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            <template x-for="booking in bookings" :key="booking.id">
                                <tr>
                                    <td class="p-3 font-mono font-bold">
                                        <a :href="`/bookings/${booking.letter_slug}`" class="text-indigo-600 hover:text-indigo-900 underline" x-text="booking.letter_number"></a>
                                    </td>
                                    
                                    <td class="p-3" x-text="booking.user ? booking.user.name : '-'"></td>
                                    
                                    <td class="p-3 font-medium">
                                        <div x-text="booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model} (${booking.vehicle.plate_number})` : '-'"></div>
                                    </td>
                                    
                                    <td class="p-3 text-xs whitespace-nowrap">
                                        <div><strong>Mulai:</strong> <span x-text="formatDate(booking.start_date)"></span></div>
                                        <div><strong>Selesai:</strong> <span x-text="formatDate(booking.end_date)"></span></div>
                                    </td>
                                    
                                    <td class="p-3">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full whitespace-nowrap uppercase"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': booking.status === 'pending',
                                                'bg-amber-100 text-amber-800': booking.status === 'waiting',
                                                'bg-blue-100 text-blue-800': booking.status === 'approved',
                                                'bg-purple-100 text-purple-800': booking.status === 'on_trip',
                                                'bg-indigo-100 text-indigo-800': booking.status === 'returned',
                                                'bg-cyan-100 text-cyan-800': booking.status === 'checked',
                                                'bg-emerald-100 text-emerald-800': booking.status === 'completed',
                                                'bg-gray-200 text-gray-700': booking.status === 'canceled',
                                                'bg-rose-100 text-rose-800': booking.status === 'rejected'
                                            }"
                                            x-text="booking.status.replace('_', ' ')">
                                        </span>
                                    </td>

                                    <td class="p-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <template x-if="booking.status === 'rejected' && booking.user_id === {{ Auth::id() }}">
                                                <a :href="`/bookings/${booking.letter_slug}/edit`" 
                                                class="w-full py-2 px-2 border border-amber-500/40 text-amber-700 bg-amber-50/50 hover:bg-amber-100/60 hover:border-amber-500 font-semibold rounded-xl text-xs text-center inline-flex items-center justify-center gap-2 transition-all duration-150 ease-in-out">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current shrink-0 text-amber-600" viewBox="0 0 24 24">
                                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                                    </svg>
                                                    <span>Perbaiki Permohonan</span>
                                                </a>
                                            </template>
                                            <template x-if="booking.status === 'approved' && canOperateTrip(booking)">
                                                <button type="button" 
                                                        @click="openStartTripModal(booking)" 
                                                        class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
                                                        <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/>
                                                    </svg>
                                                    <span>Mulai Perjalanan</span>
                                                </button>
                                            </template>
                                            <template x-if="booking.status === 'on_trip' && canOperateTrip(booking)">
                                                <button type="button" 
                                                        @click="openCompleteModal(booking)" 
                                                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-purple-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                                    </svg>
                                                    <span>Perjalanan Selesai</span>
                                                </button>
                                            </template>

                                            @if(in_array(Auth::user()->role, ['admin_lap', 'admin_app']))
                                                <template x-if="booking.status === 'pending'">
                                                    <button type="button" 
                                                            @click="openReviewModal(booking);" 
                                                            class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/30 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current text-indigo-600 shrink-0" viewBox="0 0 24 24">
                                                            <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                                        </svg>
                                                        <span>Tinjau & Plot Armada</span>
                                                    </button>
                                                </template>
                                                <template x-if="booking.status === 'returned'">
                                                    <button type="button" 
                                                            @click="openVerifyReturnModal(booking)" 
                                                            class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 active:bg-cyan-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-cyan-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                                            <path d="m9 14 2 2 4-4"/>
                                                        </svg>
                                                        <span>Tinjau Kepulangan</span>
                                                    </button>
                                                </template>
                                            @endif

                                            @if(Auth::user()->role === 'approver')
                                                <template x-if="booking.status === 'waiting'">
                                                    <button type="button" 
                                                            @click="openApproverModal(booking, 'waiting')" 
                                                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                                            <path d="m9 14 2 2 4-4"/>
                                                        </svg>
                                                        <span>Tinjau & Setujui</span>
                                                    </button>
                                                </template>
                                                <template x-if="booking.status === 'checked'">
                                                    <button type="button" 
                                                            @click="openApproverModal(booking, 'checked')" 
                                                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/50 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                            <polyline points="14 2 14 8 20 8"/>
                                                            <path d="m9 15 2 2 4-4"/>
                                                        </svg>
                                                        <span>Sahkan Pengembalian</span>
                                                    </button>
                                                </template>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- MENU TITIK TIGA DESKTOP --}}
                                    <td class="p-3 w-10 text-center relative" x-data="{ open: false }">
                                        <button @click="open = !open" @click.away="open = false" class="p-1.5 text-gray-400 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition focus:outline-none" aria-label="Menu Opsi Aksi">
                                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>

                                        <div x-show="open" x-cloak class="absolute right-0 top-full mt-1 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 z-50 text-left text-xs space-y-0.5">
                                            <template x-if="booking.user_id === {{ Auth::id() }} && booking.status === 'pending'">
                                                <a :href="`/bookings/${booking.letter_slug}/edit`" class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-amber-50 hover:text-amber-600 font-medium transition">
                                                    <!-- Icon Edit/Pencil Amber -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 20h9"/>
                                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                                    </svg>
                                                    <span>Edit Permohonan</span>
                                                </a>
                                            </template>

                                            <template x-if="booking.user_id === {{ Auth::id() }} && ['pending', 'waiting', 'approved'].includes(booking.status)">
                                                <button type="button" @click="openCancelBookingModal(booking); open = false;" class="w-full flex items-center gap-2 px-4 py-2 text-amber-600 hover:bg-amber-50 font-bold transition text-left">
                                                    <!-- Icon Ban/Cancel Amber -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"/>
                                                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                                    </svg>
                                                    <span>Batalkan Pengajuan</span>
                                                </button>
                                            </template>

                                            @if(in_array(Auth::user()->role, ['admin_lap', 'admin_app']))
                                                <template x-if="booking.status === 'waiting'">
                                                    <div class="space-y-0.5 border-t border-gray-100 pt-1 mt-1">
                                                        <button type="button" @click="openReviewModal(booking); open = false;" class="w-full flex items-center gap-2 px-4 py-2 text-indigo-600 hover:bg-indigo-50 font-medium transition text-left">
                                                            <!-- Icon Re-assign / Refresh Indigo -->
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                                                <path d="M3 3v5h5"/>
                                                                <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                                                                <path d="M16 16h5v5"/>
                                                            </svg>
                                                            <span>Re-assign Armada</span>
                                                        </button>
                                                        <button type="button" @click="openCancelApprovalModal(booking); open = false;" class="w-full flex items-center gap-2 px-4 py-2 text-rose-600 hover:bg-rose-50 font-bold transition text-left">
                                                            <!-- Icon X Circle Rose -->
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                                <circle cx="12" cy="12" r="10"/>
                                                                <line x1="15" y1="9" x2="9" y2="15"/>
                                                                <line x1="9" y1="9" x2="15" y2="15"/>
                                                            </svg>
                                                            <span>Batalkan Persetujuan</span>
                                                        </button>
                                                    </div>
                                                </template>
                                            @endif

                                            <button type="button" @click="openRouteDetailModal(booking); open = false;" class="w-full flex items-center gap-2 px-4 py-2 text-indigo-600 hover:bg-indigo-50 font-medium transition text-left border-t border-gray-100 mt-1 pt-1">
                                                <!-- Icon Map / Route Indigo -->
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <polygon points="1 6 1 22 8 18 15 22 23 18 23 2 15 6 8 2 1 6"/>
                                                    <line x1="8" y1="2" x2="8" y2="18"/>
                                                    <line x1="15" y1="6" x2="15" y2="22"/>
                                                </svg>
                                                <span>Detail Rute & Tim</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4" x-show="search === ''">
                {{ $bookings->links('vendor.pagination.tailwind') }}
            </div>

        </div>

        @include('bookings.modals.review')
        @include('bookings.modals.approver')
        @include('bookings.modals.verify-return')
        @include('bookings.modals.route-detail')
        @include('bookings.modals.start-trip')
        @include('bookings.modals.complete-trip')
        @include('bookings.modals.cancel-booking')
        @include('bookings.modals.cancel-approval')
    </div>
</x-app-layout>