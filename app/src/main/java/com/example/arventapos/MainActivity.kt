package com.example.arventapos

import android.Manifest
import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.CameraSelector
import androidx.camera.core.ExperimentalGetImage
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.Preview as CameraPreview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.foundation.background
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateMapOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.material3.TextField
import androidx.compose.material3.TextFieldDefaults
import androidx.core.content.ContextCompat
import androidx.lifecycle.LifecycleOwner
import coil.compose.AsyncImage
import com.example.arventapos.ui.theme.ArventaPOSTheme
import com.google.mlkit.vision.barcode.BarcodeScannerOptions
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage
import java.io.ByteArrayOutputStream
import java.io.BufferedReader
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL
import java.nio.charset.Charset
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.UUID
import java.util.concurrent.Executors
import kotlin.math.roundToInt
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            ArventaPOSTheme {
                ArventaApp()
            }
        }
    }
}

data class StoreSetting(
    val storeName: String,
    val businessType: String,
    val address: String,
    val logoUrl: String?,
    val qrisImageUrl: String?,
    val receiptQrImageUrl: String?,
    val themeColor: Color,
    val textColor: Color,
    val secondaryTextColor: Color,
    val priceTextColor: Color,
    val appLayout: String,
    val productCardStyle: String,
    val posOrientation: String,
    val showSku: Boolean,
    val showStock: Boolean,
    val showSearch: Boolean,
    val showCart: Boolean,
    val cartPosition: String,
    val checkoutPosition: String,
    val showOrderSummary: Boolean,
    val taxRate: Double,
    val serviceChargeRate: Double,
    val currency: String,
    val receiptFooter: String,
    val receiptHeaderTitle: String,
    val receiptHeaderSubtitle: String,
    val receiptHeaderNotes: String,
    val receiptHeaderAlignment: String,
    val receiptTemplate: String,
    val receiptPaperSize: String,
    val receiptShowLogo: Boolean,
    val receiptShowStoreName: Boolean,
    val receiptShowAddress: Boolean,
    val receiptShowDatetime: Boolean,
    val receiptShowQris: Boolean,
    val receiptShowBusinessType: Boolean,
    val receiptShowPaymentMethod: Boolean,
    val receiptShowItemPrice: Boolean,
)

data class PosItem(
    val id: Int,
    val name: String,
    val sku: String?,
    val type: String,
    val unit: String,
    val price: Int,
    val stock: Double?,
    val freeQuantity: Double?,
    val imageUrl: String?,
)

data class PairingSession(
    val baseUrl: String,
    val token: String,
    val cashierName: String,
)

data class PosState(
    val setting: StoreSetting = demoSetting,
    val items: List<PosItem> = emptyList(),
    val loading: Boolean = false,
    val error: String? = null,
)

data class CheckoutLine(
    val productId: Int?,
    val name: String,
    val unit: String,
    val unitPrice: Int,
    val quantity: Double,
    val lineTotal: Double,
    val freeQuantity: Double?,
    val chargedQuantity: Double,
)

data class SaleReceipt(
    val invoiceNumber: String,
    val subtotal: Double,
    val taxTotal: Double,
    val serviceTotal: Double,
    val grandTotal: Double,
    val paidAmount: Double,
    val changeAmount: Double,
    val paymentMethod: String,
    val items: List<SaleReceiptItem>,
)

data class SaleReceiptItem(
    val name: String,
    val unit: String,
    val quantity: Double,
    val lineTotal: Double,
)

data class PrinterDevice(
    val name: String,
    val address: String,
    val bonded: Boolean = false,
)

private object PrinterStore {
    private const val PREF = "arventa_printer"

    fun load(context: Context): PrinterDevice? {
        val pref = context.getSharedPreferences(PREF, Context.MODE_PRIVATE)
        val address = pref.getString("address", null) ?: return null
        val name = pref.getString("name", "Printer Bluetooth") ?: "Printer Bluetooth"
        return PrinterDevice(name = name, address = address, bonded = pref.getBoolean("bonded", false))
    }

    fun save(context: Context, printer: PrinterDevice) {
        context.getSharedPreferences(PREF, Context.MODE_PRIVATE).edit()
            .putString("name", printer.name)
            .putString("address", printer.address)
            .putBoolean("bonded", printer.bonded)
            .apply()
    }

    fun clear(context: Context) {
        context.getSharedPreferences(PREF, Context.MODE_PRIVATE).edit().clear().apply()
    }
}

private val demoSetting = StoreSetting(
    storeName = "Arventa POS",
    businessType = "Retail",
    address = "",
    logoUrl = null,
    qrisImageUrl = null,
    receiptQrImageUrl = null,
    themeColor = Color(0xFF2563EB),
    textColor = Color(0xFF0F172A),
    secondaryTextColor = Color(0xFF64748B),
    priceTextColor = Color(0xFF0F172A),
    appLayout = "grid",
    productCardStyle = "minimal",
    posOrientation = "portrait",
    showSku = false,
    showStock = true,
    showSearch = true,
    showCart = true,
    cartPosition = "bottom",
    checkoutPosition = "bottom",
    showOrderSummary = true,
    taxRate = 11.0,
    serviceChargeRate = 0.0,
    currency = "IDR",
    receiptFooter = "Terima kasih.",
    receiptHeaderTitle = "",
    receiptHeaderSubtitle = "",
    receiptHeaderNotes = "",
    receiptHeaderAlignment = "center",
    receiptTemplate = "classic",
    receiptPaperSize = "58",
    receiptShowLogo = false,
    receiptShowStoreName = true,
    receiptShowAddress = true,
    receiptShowDatetime = true,
    receiptShowQris = false,
    receiptShowBusinessType = true,
    receiptShowPaymentMethod = true,
    receiptShowItemPrice = true,
)

@Composable
fun ArventaApp() {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var session by remember { mutableStateOf(SessionStore.load(context)) }
    var posState by remember { mutableStateOf(PosState(loading = session != null)) }

    LaunchedEffect(session) {
        val active = session ?: return@LaunchedEffect
        posState = posState.copy(loading = true, error = null)
        posState = try {
            PosRepository.sync(active)
        } catch (error: Exception) {
            PosState(loading = false, error = error.message ?: "Gagal sync data toko.")
        }
    }

    if (session == null) {
        PairingScreen(
            onConnected = { next ->
                SessionStore.save(context, next)
                session = next
            },
        )
    } else {
        PosScreen(
            state = posState,
            session = session,
            cashierName = session?.cashierName.orEmpty(),
            onRefresh = {
                val active = session ?: return@PosScreen
                scope.launch {
                    posState = posState.copy(loading = true, error = null)
                    posState = try {
                        PosRepository.sync(active)
                    } catch (error: Exception) {
                        posState.copy(loading = false, error = error.message ?: "Gagal refresh.")
                    }
                }
            },
            onDisconnect = {
                val active = session
                session = null
                SessionStore.clear(context)
                if (active != null) {
                    scope.launch {
                        PosRepository.logout(active)
                    }
                }
            },
        )
    }
}

@Composable
private fun PairingScreen(onConnected: (PairingSession) -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var pairingInput by remember { mutableStateOf("") }
    var loading by remember { mutableStateOf(false) }
    var message by remember { mutableStateOf<String?>(null) }
    var scannerOpen by remember { mutableStateOf(false) }
    val cameraLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
        if (granted) {
            scannerOpen = true
            message = null
        } else {
            message = "Izin kamera dibutuhkan untuk scan QR pairing."
        }
    }
    val fieldColors = TextFieldDefaults.colors(
        focusedTextColor = Color(0xFF0F172A),
        unfocusedTextColor = Color(0xFF0F172A),
        focusedLabelColor = Color(0xFF0F172A),
        unfocusedLabelColor = Color(0xFF64748B),
        focusedContainerColor = Color.White,
        unfocusedContainerColor = Color.White,
        focusedIndicatorColor = Color(0xFF0F172A),
        unfocusedIndicatorColor = Color(0xFFE2E8F0),
        cursorColor = Color(0xFF0F172A),
    )

    fun connect(input: String) {
        loading = true
        message = null
        scope.launch {
            try {
                onConnected(PosRepository.pair("", input))
            } catch (error: Exception) {
                message = error.message ?: "Pairing gagal."
            } finally {
                loading = false
            }
        }
    }

    Surface(color = Color(0xFFF8FAFC), modifier = Modifier.fillMaxSize()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(20.dp),
            contentAlignment = Alignment.Center,
        ) {
            Column(
                modifier = Modifier
                    .widthIn(max = 540.dp)
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier
                            .size(46.dp)
                            .background(Color(0xFF0F172A), RoundedCornerShape(12.dp)),
                        contentAlignment = Alignment.Center,
                    ) {
                        Text("A", color = Color.White, fontWeight = FontWeight.Bold)
                    }
                    Spacer(Modifier.width(12.dp))
                    Column {
                        Text("Arventa POS", color = Color(0xFF0F172A), style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
                        Text("Pairing perangkat kasir", color = Color(0xFF64748B), style = MaterialTheme.typography.bodyMedium)
                    }
                }
                Card(colors = CardDefaults.cardColors(containerColor = Color.White), shape = RoundedCornerShape(22.dp), elevation = CardDefaults.cardElevation(defaultElevation = 3.dp)) {
                    Column(Modifier.padding(18.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Text(
                        "Scan QR dari menu Perangkat Kasir, atau masukkan 6 digit kode pairing. URL toko akan tersimpan otomatis.",
                        color = Color(0xFF475569),
                        style = MaterialTheme.typography.bodyMedium,
                    )
                    if (scannerOpen) {
                        QrPairingScanner(
                            onScanned = { payload ->
                                pairingInput = payload
                                scannerOpen = false
                                connect(payload)
                            },
                            onClose = { scannerOpen = false },
                        )
                    }
                    OutlinedTextField(
                        value = pairingInput,
                        onValueChange = { pairingInput = it },
                        label = { Text("QR pairing atau kode 6 digit") },
                        minLines = 1,
                        colors = fieldColors,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    OutlinedButton(
                        onClick = {
                            if (ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
                                scannerOpen = true
                                message = null
                            } else {
                                cameraLauncher.launch(Manifest.permission.CAMERA)
                            }
                        },
                        enabled = !loading,
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(14.dp),
                        border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
                        colors = ButtonDefaults.outlinedButtonColors(contentColor = Color(0xFF0F172A)),
                    ) {
                        Text(if (scannerOpen) "Arahkan kamera ke QR" else "Scan QR Pairing")
                    }
                    Button(
                        onClick = {
                            connect(pairingInput)
                        },
                        enabled = !loading && pairingInput.isNotBlank(),
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(14.dp),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = Color(0xFF0F172A),
                            contentColor = Color.White,
                            disabledContainerColor = Color(0xFFCBD5E1),
                            disabledContentColor = Color.White,
                        ),
                    ) {
                        if (loading) {
                            CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp, color = Color.White)
                            Spacer(Modifier.width(8.dp))
                        }
                        Text(if (loading) "Menghubungkan..." else "Hubungkan Perangkat")
                    }
                    message?.let {
                        Surface(color = Color(0xFFFEF2F2), shape = RoundedCornerShape(12.dp), border = BorderStroke(1.dp, Color(0xFFFECACA))) {
                            Text(it, color = Color(0xFFB91C1C), style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(10.dp))
                        }
                    }
                    Surface(color = Color(0xFFF8FAFC), shape = RoundedCornerShape(14.dp), border = BorderStroke(1.dp, Color(0xFFE2E8F0))) {
                        Text(
                            "Buka Web Admin tenant > Perangkat Kasir > Generate QR Pairing. Jika hanya memasukkan kode, app akan mengecek ke server Arventa lalu menyimpan URL tenant otomatis.",
                            color = Color(0xFF64748B),
                            style = MaterialTheme.typography.bodySmall,
                            modifier = Modifier.padding(12.dp),
                        )
                    }
                    }
                }
            }
        }
    }
}

@androidx.annotation.OptIn(ExperimentalGetImage::class)
@Composable
private fun QrPairingScanner(onScanned: (String) -> Unit, onClose: () -> Unit) {
    val context = LocalContext.current
    val lifecycleOwner = context as LifecycleOwner
    val cameraProviderFuture = remember { ProcessCameraProvider.getInstance(context) }
    val scanner = remember {
        BarcodeScanning.getClient(
            BarcodeScannerOptions.Builder()
                .setBarcodeFormats(Barcode.FORMAT_QR_CODE)
                .build()
        )
    }
    val cameraExecutor = remember { Executors.newSingleThreadExecutor() }
    var hasScanned by remember { mutableStateOf(false) }

    Card(colors = CardDefaults.cardColors(containerColor = Color(0xFF020617)), shape = RoundedCornerShape(18.dp)) {
        Column(Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(280.dp)
                    .background(Color.Black, RoundedCornerShape(14.dp)),
            ) {
                AndroidView(
                    modifier = Modifier.fillMaxSize(),
                    factory = { viewContext ->
                        val previewView = PreviewView(viewContext).apply {
                            scaleType = PreviewView.ScaleType.FILL_CENTER
                        }
                        val preview = CameraPreview.Builder().build().also {
                            it.setSurfaceProvider(previewView.surfaceProvider)
                        }
                        val analysis = ImageAnalysis.Builder()
                            .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                            .build()
                            .also { analyzer ->
                                analyzer.setAnalyzer(cameraExecutor) { imageProxy ->
                                    val mediaImage = imageProxy.image
                                    if (mediaImage == null || hasScanned) {
                                        imageProxy.close()
                                        return@setAnalyzer
                                    }

                                    val image = InputImage.fromMediaImage(mediaImage, imageProxy.imageInfo.rotationDegrees)
                                    scanner.process(image)
                                        .addOnSuccessListener { barcodes ->
                                            val value = barcodes.firstNotNullOfOrNull { it.rawValue }
                                            if (!value.isNullOrBlank() && !hasScanned) {
                                                hasScanned = true
                                                onScanned(value)
                                            }
                                        }
                                        .addOnCompleteListener {
                                            imageProxy.close()
                                        }
                                }
                            }

                        cameraProviderFuture.addListener({
                            val cameraProvider = cameraProviderFuture.get()
                            cameraProvider.unbindAll()
                            cameraProvider.bindToLifecycle(
                                lifecycleOwner,
                                CameraSelector.DEFAULT_BACK_CAMERA,
                                preview,
                                analysis,
                            )
                        }, ContextCompat.getMainExecutor(viewContext))

                        previewView
                    },
                )
                Box(
                    modifier = Modifier
                        .align(Alignment.Center)
                        .size(210.dp)
                        .background(Color.Transparent, RoundedCornerShape(18.dp)),
                )
                Text(
                    "Scan QR dari menu Perangkat Kasir",
                    color = Color.White,
                    modifier = Modifier
                        .align(Alignment.BottomCenter)
                        .padding(14.dp),
                    style = MaterialTheme.typography.bodySmall,
                )
            }
            OutlinedButton(onClick = onClose, modifier = Modifier.fillMaxWidth()) {
                Text("Tutup Scanner")
            }
        }
    }

    DisposableEffect(Unit) {
        onDispose {
            scanner.close()
            cameraExecutor.shutdown()
            cameraProviderFuture.get().unbindAll()
        }
    }
}

@Composable
fun PosScreen(
    state: PosState,
    session: PairingSession?,
    cashierName: String,
    onRefresh: () -> Unit,
    onDisconnect: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    val setting = state.setting
    val items = state.items
    val saleItems = remember(items) { items.filterNot { it.type == "discount" } }
    val discountItems = remember(items) { items.filter { it.type == "discount" && it.price < 0.0 } }
    val cart = remember { mutableStateMapOf<Int, Double>() }
    val subtotal = cart.entries.sumOf { (id, qty) -> items.firstOrNull { it.id == id }?.let { catalogLineTotal(it, qty) } ?: 0.0 }
    val chargeableSubtotal = subtotal.coerceAtLeast(0.0)
    val tax = chargeableSubtotal * setting.taxRate / 100
    val service = chargeableSubtotal * setting.serviceChargeRate / 100
    val total = (subtotal + tax + service).coerceAtLeast(0.0)
    val configuration = LocalConfiguration.current
    val isWide = configuration.screenWidthDp >= 700 || setting.posOrientation == "landscape"
    val useSideCart = setting.showCart && isWide && setting.cartPosition == "right"
    var checkoutOpen by remember { mutableStateOf(false) }
    var checkoutLoading by remember { mutableStateOf(false) }
    var checkoutError by remember { mutableStateOf<String?>(null) }
    var receipt by remember { mutableStateOf<SaleReceipt?>(null) }
    var printerSetupOpen by remember { mutableStateOf(false) }

    val checkoutLines = cart.entries.mapNotNull { (id, qty) ->
        items.firstOrNull { it.id == id }?.let { item ->
            CheckoutLine(
                productId = item.id,
                name = item.name,
                unit = item.unit,
                unitPrice = item.price,
                quantity = qty,
                lineTotal = catalogLineTotal(item, qty),
                freeQuantity = item.freeQuantity,
                chargedQuantity = catalogChargedQuantity(item, qty),
            )
        }
    }
    val openCheckout: () -> Unit = {
        if (checkoutLines.isNotEmpty()) {
            checkoutError = null
            checkoutOpen = true
        }
    }
    val submitCheckout: (String, Double) -> Unit = { method, paidAmount ->
        val active = session
        if (active == null) {
            checkoutError = "Perangkat belum terhubung."
        } else {
            checkoutLoading = true
            checkoutError = null
            scope.launch {
                try {
                    val sale = PosRepository.checkout(active, checkoutLines, paidAmount, method)
                    receipt = sale
                    checkoutOpen = false
                    cart.clear()
                    onRefresh()
                } catch (error: Exception) {
                    checkoutError = error.message ?: "Checkout gagal."
                } finally {
                    checkoutLoading = false
                }
            }
        }
    }

    Scaffold(
        modifier = Modifier.fillMaxSize(),
        topBar = { StoreHeader(setting, cashierName, onRefresh, { printerSetupOpen = true }, onDisconnect) },
        bottomBar = {
            if (setting.checkoutPosition == "bottom" && !useSideCart) {
                CheckoutBar(setting, subtotal, tax, service, total, checkoutLines.isNotEmpty(), openCheckout)
            }
        },
    ) { innerPadding ->
        Box(
            Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .background(Color(0xFFF8FAFC)),
        ) {
            if (state.loading) {
                CircularProgressIndicator(Modifier.align(Alignment.Center), color = setting.themeColor)
            } else if (state.error != null) {
                ErrorState(state.error, onRefresh, Modifier.align(Alignment.Center))
            } else {
                BoxWithConstraints(Modifier.fillMaxSize()) {
                    val contentPadding = if (maxWidth < 600.dp) 12.dp else 16.dp
                    val contentUseSideCart = useSideCart && maxWidth >= 820.dp
                    val cartWidth = when {
                        maxWidth >= 1200.dp -> 320.dp
                        maxWidth >= 980.dp -> 292.dp
                        else -> 260.dp
                    }
                    val tileMinWidth = when {
                        maxWidth >= 1200.dp -> 220.dp
                        maxWidth >= 900.dp -> 190.dp
                        else -> 156.dp
                    }

                    if (contentUseSideCart) {
                        Row(Modifier.fillMaxSize().padding(contentPadding), horizontalArrangement = Arrangement.spacedBy(14.dp)) {
                            ProductCatalog(setting, saleItems, cart, Modifier.weight(1f).fillMaxSize(), tileMinWidth)
                            CartPanel(setting, items, discountItems, cart, subtotal, tax, service, total, checkoutLines.isNotEmpty(), openCheckout, Modifier.width(cartWidth).fillMaxHeight())
                        }
                    } else {
                        Column(Modifier.fillMaxSize().padding(contentPadding), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                            ProductCatalog(setting, saleItems, cart, Modifier.weight(1f), tileMinWidth)
                            if (setting.showCart) {
                                CartPanel(setting, items, discountItems, cart, subtotal, tax, service, total, checkoutLines.isNotEmpty(), openCheckout, Modifier.fillMaxWidth())
                            }
                        }
                    }
                }
            }

            if (setting.checkoutPosition == "floating") {
                Button(
                    onClick = openCheckout,
                    enabled = checkoutLines.isNotEmpty(),
                    modifier = Modifier.align(Alignment.BottomEnd).padding(18.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = setting.themeColor,
                        contentColor = Color.White,
                        disabledContainerColor = setting.themeColor.copy(alpha = 0.35f),
                        disabledContentColor = Color.White.copy(alpha = 0.72f),
                    ),
                ) {
                    Text(if (setting.showCart) "Checkout" else "Bayar")
                }
            }
        }
    }

    if (checkoutOpen) {
        CheckoutDialog(
            setting = setting,
            lines = checkoutLines,
            subtotal = subtotal,
            tax = tax,
            service = service,
            total = total,
            loading = checkoutLoading,
            error = checkoutError,
            onDismiss = { if (!checkoutLoading) checkoutOpen = false },
            onSubmit = submitCheckout,
        )
    }

    receipt?.let { sale ->
        ReceiptDialog(
            setting = setting,
            sale = sale,
            onDone = { receipt = null },
        )
    }

    if (printerSetupOpen) {
        PrinterSetupDialog(
            setting = setting,
            onDismiss = { printerSetupOpen = false },
        )
    }
}

@Composable
private fun StoreHeader(setting: StoreSetting, cashierName: String, onRefresh: () -> Unit, onPrinterSetup: () -> Unit, onDisconnect: () -> Unit) {
    val isCompact = LocalConfiguration.current.screenWidthDp < 520
    Surface(color = setting.themeColor, shadowElevation = 2.dp) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(horizontal = if (isCompact) 12.dp else 16.dp, vertical = if (isCompact) 12.dp else 18.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(
                modifier = Modifier.size(if (isCompact) 32.dp else 34.dp).background(Color.White.copy(alpha = 0.18f), RoundedCornerShape(8.dp)),
                contentAlignment = Alignment.Center,
            ) { Text("A", color = Color.White, fontWeight = FontWeight.Bold) }
            Spacer(Modifier.width(if (isCompact) 8.dp else 12.dp))
            Column(Modifier.weight(1f)) {
                Text(setting.storeName, color = Color.White, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                if (!isCompact) {
                    Text(setting.businessType, color = Color.White.copy(alpha = 0.82f), style = MaterialTheme.typography.bodySmall, maxLines = 1, overflow = TextOverflow.Ellipsis)
                }
            }
            TextButton(
                onClick = onRefresh,
                colors = ButtonDefaults.textButtonColors(contentColor = Color.White),
                contentPadding = PaddingValues(horizontal = if (isCompact) 8.dp else 12.dp),
            ) {
                Text("Sync")
            }
            Box(
                modifier = Modifier
                    .padding(start = 2.dp, end = if (isCompact) 6.dp else 8.dp)
                    .size(if (isCompact) 34.dp else 36.dp)
                    .clip(RoundedCornerShape(999.dp))
                    .background(Color.White.copy(alpha = 0.16f))
                    .clickable(onClick = onPrinterSetup),
                contentAlignment = Alignment.Center,
            ) {
                PrinterGlyph(Color.White)
            }
            Box(
                modifier = Modifier
                    .size(if (isCompact) 34.dp else 36.dp)
                    .clip(RoundedCornerShape(999.dp))
                    .background(Color.White.copy(alpha = 0.16f))
                    .clickable(onClick = onDisconnect),
                contentAlignment = Alignment.Center,
            ) {
                LogoutGlyph(Color.White)
            }
        }
    }
}

@Composable
private fun PrinterGlyph(color: Color) {
    Canvas(modifier = Modifier.size(18.dp)) {
        val stroke = Stroke(width = 2.0f)
        drawRoundRect(
            color = color,
            topLeft = androidx.compose.ui.geometry.Offset(size.width * 0.25f, size.height * 0.08f),
            size = androidx.compose.ui.geometry.Size(size.width * 0.5f, size.height * 0.28f),
            cornerRadius = androidx.compose.ui.geometry.CornerRadius(1.5f, 1.5f),
            style = stroke,
        )
        drawRoundRect(
            color = color,
            topLeft = androidx.compose.ui.geometry.Offset(size.width * 0.12f, size.height * 0.34f),
            size = androidx.compose.ui.geometry.Size(size.width * 0.76f, size.height * 0.42f),
            cornerRadius = androidx.compose.ui.geometry.CornerRadius(2.2f, 2.2f),
            style = stroke,
        )
        drawRoundRect(
            color = color,
            topLeft = androidx.compose.ui.geometry.Offset(size.width * 0.28f, size.height * 0.62f),
            size = androidx.compose.ui.geometry.Size(size.width * 0.44f, size.height * 0.3f),
            cornerRadius = androidx.compose.ui.geometry.CornerRadius(1.5f, 1.5f),
            style = stroke,
        )
        drawCircle(color = color, radius = 1.3f, center = center.copy(x = size.width * 0.72f, y = size.height * 0.5f))
    }
}

@Composable
private fun LogoutGlyph(color: Color) {
    Canvas(modifier = Modifier.size(18.dp)) {
        val stroke = Stroke(width = 2.2f)
        drawLine(color, start = center.copy(x = size.width * 0.18f, y = size.height * 0.2f), end = center.copy(x = size.width * 0.18f, y = size.height * 0.8f), strokeWidth = 2.2f)
        drawLine(color, start = center.copy(x = size.width * 0.18f, y = size.height * 0.2f), end = center.copy(x = size.width * 0.48f, y = size.height * 0.2f), strokeWidth = 2.2f)
        drawLine(color, start = center.copy(x = size.width * 0.18f, y = size.height * 0.8f), end = center.copy(x = size.width * 0.48f, y = size.height * 0.8f), strokeWidth = 2.2f)
        drawLine(color, start = center.copy(x = size.width * 0.42f, y = size.height * 0.5f), end = center.copy(x = size.width * 0.84f, y = size.height * 0.5f), strokeWidth = 2.2f)
        drawLine(color, start = center.copy(x = size.width * 0.66f, y = size.height * 0.32f), end = center.copy(x = size.width * 0.84f, y = size.height * 0.5f), strokeWidth = 2.2f)
        drawLine(color, start = center.copy(x = size.width * 0.66f, y = size.height * 0.68f), end = center.copy(x = size.width * 0.84f, y = size.height * 0.5f), strokeWidth = 2.2f)
    }
}

@Composable
private fun ProductCatalog(
    setting: StoreSetting,
    items: List<PosItem>,
    cart: MutableMap<Int, Double>,
    modifier: Modifier = Modifier,
    tileMinWidth: androidx.compose.ui.unit.Dp = 168.dp,
) {
    val addItem = { item: PosItem ->
        val step = quantityStep(item.unit)
        val current = cart[item.id] ?: 0.0
        val next = current + step
        if (item.stock == null || next <= item.stock + 0.0001) {
            cart[item.id] = normalizeQuantity(next)
        }
    }
    val removeItem = { item: PosItem ->
        val next = (cart[item.id] ?: 0.0) - quantityStep(item.unit)
        if (next <= 0.0) cart.remove(item.id) else cart[item.id] = normalizeQuantity(next)
    }
    val setItemQuantity = { item: PosItem, quantity: Double ->
        if (quantity <= 0.0) {
            cart.remove(item.id)
        } else {
            cart[item.id] = normalizeQuantity(quantity)
        }
    }
    var searchQuery by remember { mutableStateOf("") }
    val visibleItems = remember(items, searchQuery) {
        val query = searchQuery.trim()
        if (query.isBlank()) {
            items
        } else {
            items.filter {
                it.name.contains(query, ignoreCase = true) ||
                    it.sku?.contains(query, ignoreCase = true) == true ||
                    it.type.contains(query, ignoreCase = true)
            }
        }
    }

    Column(modifier) {
        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
            Text("Produk & Layanan", color = setting.textColor, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
        }
        if (setting.showSearch) {
            SearchField(
                value = searchQuery,
                onValueChange = { searchQuery = it },
                setting = setting,
                modifier = Modifier.fillMaxWidth().padding(top = 10.dp, bottom = 10.dp),
            )
        } else {
            Spacer(Modifier.height(10.dp))
        }

        if (items.isEmpty()) {
            EmptyCatalog(Modifier.fillMaxSize(), "Belum ada item", "Tambahkan produk dari web admin.")
            return
        }

        if (visibleItems.isEmpty()) {
            EmptyCatalog(Modifier.fillMaxSize(), "Item tidak ditemukan", "Coba kata kunci lain.")
            return
        }

        if (setting.appLayout == "grid") {
            LazyVerticalGrid(
                columns = GridCells.Adaptive(minSize = tileMinWidth),
                modifier = Modifier.fillMaxSize(),
                verticalArrangement = Arrangement.spacedBy(10.dp),
                horizontalArrangement = Arrangement.spacedBy(10.dp),
            ) {
                items(visibleItems) { item ->
                    ProductTile(item, cart[item.id] ?: 0.0, setting, { addItem(item) }, { removeItem(item) }, { setItemQuantity(item, it) })
                }
            }
        } else {
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                verticalArrangement = Arrangement.spacedBy(if (setting.appLayout == "compact") 6.dp else 10.dp),
            ) {
                items(visibleItems) { item ->
                    ProductRow(item, cart[item.id] ?: 0.0, setting, setting.appLayout == "compact", { addItem(item) }, { removeItem(item) }, { setItemQuantity(item, it) })
                }
            }
        }
    }
}

@Composable
private fun SearchField(
    value: String,
    onValueChange: (String) -> Unit,
    setting: StoreSetting,
    modifier: Modifier = Modifier,
) {
    Surface(
        modifier = modifier.height(46.dp),
        shape = RoundedCornerShape(999.dp),
        color = Color.White,
        border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
        shadowElevation = 2.dp,
    ) {
        TextField(
            value = value,
            onValueChange = onValueChange,
            singleLine = true,
            placeholder = {
                Text("Cari produk atau layanan", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
            },
            leadingIcon = {
                SearchGlyph(setting.secondaryTextColor)
            },
            colors = TextFieldDefaults.colors(
                focusedTextColor = setting.textColor,
                unfocusedTextColor = setting.textColor,
                focusedContainerColor = Color.Transparent,
                unfocusedContainerColor = Color.Transparent,
                disabledContainerColor = Color.Transparent,
                focusedIndicatorColor = Color.Transparent,
                unfocusedIndicatorColor = Color.Transparent,
                cursorColor = setting.themeColor,
            ),
            textStyle = MaterialTheme.typography.bodySmall,
            modifier = Modifier.fillMaxSize(),
        )
    }
}

@Composable
private fun SearchGlyph(color: Color) {
    Canvas(modifier = Modifier.size(18.dp)) {
        drawCircle(
            color = color,
            radius = size.minDimension * 0.32f,
            center = center.copy(x = size.width * 0.43f, y = size.height * 0.43f),
            style = Stroke(width = 2.2f),
        )
        drawLine(
            color = color,
            start = center.copy(x = size.width * 0.64f, y = size.height * 0.64f),
            end = center.copy(x = size.width * 0.84f, y = size.height * 0.84f),
            strokeWidth = 2.2f,
        )
    }
}

@Composable
private fun ProductRow(item: PosItem, quantity: Double, setting: StoreSetting, compact: Boolean, onAdd: () -> Unit, onRemove: () -> Unit, onSetQuantity: (Double) -> Unit) {
    Card(shape = RoundedCornerShape(10.dp), colors = CardDefaults.cardColors(containerColor = Color.White), modifier = Modifier.fillMaxWidth()) {
        Row(Modifier.clickable(onClick = onAdd).padding(if (compact) 10.dp else 14.dp), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(item.name, color = setting.textColor, fontWeight = FontWeight.SemiBold)
                Text(productMeta(item, setting), color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                Text("${formatMoney(item.price, setting.currency)} / ${item.unit}", color = setting.priceTextColor, fontWeight = FontWeight.Bold)
            }
            QuantityStepper(quantity, item.unit, item.stock, setting, onAdd, onRemove, onSetQuantity)
        }
    }
}

@Composable
private fun ProductTile(item: PosItem, quantity: Double, setting: StoreSetting, onAdd: () -> Unit, onRemove: () -> Unit, onSetQuantity: (Double) -> Unit) {
    Card(shape = RoundedCornerShape(10.dp), colors = CardDefaults.cardColors(containerColor = Color.White), modifier = Modifier.fillMaxWidth().heightIn(min = 170.dp).clickable(onClick = onAdd)) {
        Column(Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            if (setting.productCardStyle == "image") {
                ProductImage(item, setting, Modifier.fillMaxWidth().height(92.dp))
            }
            Text(item.name, color = setting.textColor, fontWeight = FontWeight.SemiBold, maxLines = 2, overflow = TextOverflow.Ellipsis)
            Text(productMeta(item, setting), color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall, maxLines = 2, overflow = TextOverflow.Ellipsis)
            Text("${formatMoney(item.price, setting.currency)} / ${item.unit}", color = setting.priceTextColor, fontWeight = FontWeight.Bold, maxLines = 1, overflow = TextOverflow.Ellipsis)
            QuantityStepper(quantity, item.unit, item.stock, setting, onAdd, onRemove, onSetQuantity)
        }
    }
}

@Composable
private fun ProductImage(item: PosItem, setting: StoreSetting, modifier: Modifier = Modifier) {
    Box(modifier.clip(RoundedCornerShape(10.dp)).background(Color(0xFFF1F5F9)), contentAlignment = Alignment.Center) {
        if (!item.imageUrl.isNullOrBlank()) {
            AsyncImage(
                model = item.imageUrl,
                contentDescription = item.name,
                modifier = Modifier.fillMaxSize(),
                contentScale = ContentScale.Crop,
            )
        } else {
            Text(item.name.take(1).uppercase(), color = setting.themeColor, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
private fun QuantityStepper(quantity: Double, unit: String, stock: Double?, setting: StoreSetting, onAdd: () -> Unit, onRemove: () -> Unit, onSetQuantity: (Double) -> Unit) {
    var customOpen by remember { mutableStateOf(false) }
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
        QuantityButton(label = "-", enabled = quantity > 0.0, background = Color(0xFFCBD5E1), onClick = onRemove)
        Surface(
            modifier = Modifier
                .padding(horizontal = 8.dp)
                .weight(1f)
                .height(34.dp)
                .clickable { customOpen = true },
            shape = RoundedCornerShape(999.dp),
            color = Color.White,
            border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
        ) {
            Box(contentAlignment = Alignment.Center) {
                Text(formatQuantity(quantity), color = setting.themeColor, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodySmall)
            }
        }
        QuantityButton(label = "+", enabled = true, background = setting.themeColor, onClick = onAdd)
    }
    if (customOpen) {
        CustomQuantityDialog(
            quantity = quantity,
            unit = unit,
            stock = stock,
            setting = setting,
            onDismiss = { customOpen = false },
            onSave = {
                onSetQuantity(it)
                customOpen = false
            },
        )
    }
}

@Composable
private fun QuantityButton(label: String, enabled: Boolean, background: Color, onClick: () -> Unit) {
    Button(
        onClick = onClick,
        enabled = enabled,
        modifier = Modifier.size(width = 44.dp, height = 34.dp),
        shape = RoundedCornerShape(999.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = background,
            contentColor = bestContentColor(background),
            disabledContainerColor = Color(0xFFE2E8F0),
            disabledContentColor = Color.White,
        ),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(0.dp),
    ) {
        Text(label, fontWeight = FontWeight.Bold)
    }
}

@Composable
private fun CustomQuantityDialog(
    quantity: Double,
    unit: String,
    stock: Double?,
    setting: StoreSetting,
    onDismiss: () -> Unit,
    onSave: (Double) -> Unit,
) {
    var input by remember(quantity) { mutableStateOf(if (quantity > 0.0) formatQuantity(quantity) else "") }
    var error by remember { mutableStateOf<String?>(null) }
    val inputColors = TextFieldDefaults.colors(
        focusedTextColor = setting.textColor,
        unfocusedTextColor = setting.textColor,
        focusedLabelColor = setting.themeColor,
        unfocusedLabelColor = setting.secondaryTextColor,
        focusedContainerColor = Color.White,
        unfocusedContainerColor = Color.White,
        focusedIndicatorColor = setting.themeColor,
        unfocusedIndicatorColor = Color(0xFFE2E8F0),
        cursorColor = setting.themeColor,
    )

    AlertDialog(
        onDismissRequest = onDismiss,
        containerColor = Color.White,
        titleContentColor = setting.textColor,
        textContentColor = setting.textColor,
        title = { Text("Atur jumlah", color = setting.textColor, fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = input,
                    onValueChange = {
                        input = it
                        error = null
                    },
                    label = { Text("Jumlah ($unit)") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    colors = inputColors,
                    modifier = Modifier.fillMaxWidth(),
                )
                Text(
                    "Isi 0 untuk menghapus item dari cart. Stok tersedia: ${stock?.let { "${formatQuantity(it)} $unit" } ?: "tidak dibatasi"}.",
                    color = setting.secondaryTextColor,
                    style = MaterialTheme.typography.bodySmall,
                )
                error?.let {
                    Text(it, color = Color(0xFFDC2626), style = MaterialTheme.typography.bodySmall)
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val value = input.trim().replace(',', '.').toDoubleOrNull()
                    when {
                        value == null -> error = "Jumlah harus berupa angka."
                        value < 0.0 -> error = "Jumlah tidak boleh minus."
                        !allowsFractionalUnit(unit) && value % 1.0 != 0.0 -> error = "Satuan pcs harus bilangan utuh."
                        stock != null && value > stock + 0.0001 -> error = "Jumlah melebihi stok ${formatQuantity(stock)} $unit."
                        else -> onSave(normalizeQuantity(value))
                    }
                },
                colors = ButtonDefaults.buttonColors(
                    containerColor = setting.themeColor,
                    contentColor = bestContentColor(setting.themeColor),
                ),
            ) {
                Text("Simpan")
            }
        },
        dismissButton = {
            TextButton(
                onClick = onDismiss,
                colors = ButtonDefaults.textButtonColors(contentColor = setting.secondaryTextColor),
            ) {
                Text("Batal")
            }
        },
    )
}

@Composable
private fun CartPanel(
    setting: StoreSetting,
    items: List<PosItem>,
    discountItems: List<PosItem>,
    cart: MutableMap<Int, Double>,
    subtotal: Double,
    tax: Double,
    service: Double,
    total: Double,
    canCheckout: Boolean,
    onCheckout: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val selected = cart.entries.mapNotNull { (id, qty) -> items.firstOrNull { it.id == id }?.let { it to qty } }
    var discountDialogOpen by remember { mutableStateOf(false) }
    Card(modifier = modifier, shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Cart", color = setting.textColor, fontWeight = FontWeight.Bold)
                Text("${formatQuantity(selected.sumOf { it.second })} item", color = setting.secondaryTextColor)
            }
            if (discountItems.isNotEmpty()) {
                OutlinedButton(
                    onClick = { discountDialogOpen = true },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.outlinedButtonColors(contentColor = setting.themeColor),
                    border = BorderStroke(1.dp, setting.themeColor.copy(alpha = 0.24f)),
                ) {
                    Text("Diskon")
                }
            }
            if (selected.isEmpty()) {
                Text("Belum ada item dipilih", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
            } else {
                selected.forEach { (item, qty) ->
                    Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
                            Text("${formatQuantity(qty)} ${item.unit} x ${item.name}", color = setting.secondaryTextColor, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f))
                            Text(formatMoney(catalogLineTotal(item, qty), setting.currency), color = setting.priceTextColor, fontWeight = FontWeight.SemiBold)
                        }
                        if (item.type == "discount") {
                            TextButton(
                                onClick = { cart.remove(item.id) },
                                contentPadding = PaddingValues(0.dp),
                                modifier = Modifier.height(28.dp),
                                colors = ButtonDefaults.textButtonColors(contentColor = setting.secondaryTextColor),
                            ) {
                                Text("Hapus diskon", style = MaterialTheme.typography.bodySmall)
                            }
                        }
                    }
                }
            }
            if (setting.showOrderSummary) {
                SummaryLine("Subtotal", subtotal, setting, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Pajak", tax, setting, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Service", service, setting, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Total", total, setting, setting.textColor, setting.priceTextColor, true)
            }
            if (setting.checkoutPosition == "cart") {
                CheckoutActionButton(setting, canCheckout, onCheckout, Modifier.fillMaxWidth(), "Checkout")
            }
        }
    }
    if (discountDialogOpen) {
        AlertDialog(
            onDismissRequest = { discountDialogOpen = false },
            containerColor = Color.White,
            title = { Text("Pilih Diskon", color = setting.textColor, fontWeight = FontWeight.Bold) },
            text = {
                LazyColumn(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(discountItems) { discount ->
                        val applied = cart.containsKey(discount.id)
                        Surface(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable {
                                    cart[discount.id] = 1.0
                                    discountDialogOpen = false
                                },
                            shape = RoundedCornerShape(12.dp),
                            color = if (applied) setting.themeColor.copy(alpha = 0.08f) else Color(0xFFF8FAFC),
                            border = BorderStroke(1.dp, if (applied) setting.themeColor.copy(alpha = 0.35f) else Color(0xFFE2E8F0)),
                        ) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically,
                            ) {
                                Column(Modifier.weight(1f)) {
                                    Text(discount.name, color = setting.textColor, fontWeight = FontWeight.SemiBold)
                                    Text(if (applied) "Sudah masuk cart" else "Tap untuk pakai diskon", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                                }
                                Text(formatMoney(discount.price, setting.currency), color = setting.priceTextColor, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            },
            confirmButton = {
                TextButton(
                    onClick = { discountDialogOpen = false },
                    colors = ButtonDefaults.textButtonColors(contentColor = setting.secondaryTextColor),
                ) {
                    Text("Tutup")
                }
            },
        )
    }
}

@Composable
private fun CheckoutBar(setting: StoreSetting, subtotal: Double, tax: Double, service: Double, total: Double, canCheckout: Boolean, onCheckout: () -> Unit) {
    Surface(color = Color.White, shadowElevation = 8.dp) {
        Column(Modifier.fillMaxWidth().padding(16.dp)) {
            if (setting.showOrderSummary) {
                SummaryLine("Subtotal", subtotal, setting, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Pajak", tax, setting, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Service", service, setting, setting.secondaryTextColor, setting.priceTextColor)
                Spacer(Modifier.height(8.dp))
            }
            SummaryLine("Total", total, setting, setting.textColor, setting.priceTextColor, true)
            Spacer(Modifier.height(10.dp))
            CheckoutActionButton(setting, canCheckout, onCheckout, Modifier.fillMaxWidth(), if (setting.showCart) "Checkout" else "Bayar")
            Text(setting.receiptFooter, modifier = Modifier.fillMaxWidth().padding(top = 8.dp), color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
        }
    }
}

@Composable
private fun CheckoutActionButton(setting: StoreSetting, enabled: Boolean, onCheckout: () -> Unit, modifier: Modifier = Modifier, label: String = "Checkout") {
    Button(
        onClick = onCheckout,
        enabled = enabled,
        modifier = modifier,
        shape = RoundedCornerShape(14.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = setting.themeColor,
            contentColor = Color.White,
            disabledContainerColor = setting.themeColor.copy(alpha = 0.35f),
            disabledContentColor = Color.White.copy(alpha = 0.72f),
        ),
    ) {
        Text(label)
    }
}

@Composable
private fun CheckoutDialog(
    setting: StoreSetting,
    lines: List<CheckoutLine>,
    subtotal: Double,
    tax: Double,
    service: Double,
    total: Double,
    loading: Boolean,
    error: String?,
    onDismiss: () -> Unit,
    onSubmit: (String, Double) -> Unit,
) {
    var method by remember { mutableStateOf("cash") }
    var paidInput by remember(total) { mutableStateOf(formatQuantity(total)) }
    val paidAmount = if (method == "qris") total else paidInput.replace(',', '.').toDoubleOrNull() ?: 0.0
    val change = paidAmount - total
    val canSubmit = !loading && lines.isNotEmpty() && paidAmount >= total
    val dialogBackground = Color.White
    val inputColors = TextFieldDefaults.colors(
        focusedTextColor = setting.textColor,
        unfocusedTextColor = setting.textColor,
        focusedLabelColor = setting.themeColor,
        unfocusedLabelColor = setting.secondaryTextColor,
        focusedContainerColor = Color.White,
        unfocusedContainerColor = Color.White,
        focusedIndicatorColor = setting.themeColor,
        unfocusedIndicatorColor = Color(0xFFE2E8F0),
        cursorColor = setting.themeColor,
    )

    AlertDialog(
        onDismissRequest = onDismiss,
        containerColor = dialogBackground,
        titleContentColor = setting.textColor,
        textContentColor = setting.textColor,
        title = { Text("Checkout", color = setting.textColor, fontWeight = FontWeight.Bold) },
        text = {
            LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                item {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        lines.forEach { line ->
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
                                Column(Modifier.weight(1f)) {
                                    Text("${formatQuantity(line.quantity)} ${line.unit} x ${line.name}", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                                    checkoutRuleDescription(line)?.let {
                                        Text(it, color = setting.themeColor, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
                                    }
                                }
                                Text(formatMoney(line.lineTotal, setting.currency), color = setting.priceTextColor, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
                            }
                        }
                        SummaryLine("Subtotal", subtotal, setting, setting.secondaryTextColor, setting.priceTextColor)
                        SummaryLine("Pajak", tax, setting, setting.secondaryTextColor, setting.priceTextColor)
                        SummaryLine("Service", service, setting, setting.secondaryTextColor, setting.priceTextColor)
                        SummaryLine("Total", total, setting, setting.textColor, setting.priceTextColor, true)
                    }
                }
                item {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        PaymentMethodButton("Tunai", method == "cash", setting.themeColor, Modifier.weight(1f)) { method = "cash" }
                        PaymentMethodButton("QRIS", method == "qris", setting.themeColor, Modifier.weight(1f)) { method = "qris" }
                    }
                }
                if (method == "cash") {
                    item {
                        OutlinedTextField(
                            value = paidInput,
                            onValueChange = { paidInput = it },
                            label = { Text("Nominal diterima") },
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            colors = inputColors,
                            modifier = Modifier.fillMaxWidth(),
                        )
                        Text("Kembalian ${formatMoney(change.coerceAtLeast(0.0), setting.currency)}", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 6.dp))
                    }
                } else {
                    item {
                        Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color(0xFFF8FAFC))) {
                            Column(Modifier.fillMaxWidth().padding(14.dp), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(8.dp)) {
                                if (!setting.qrisImageUrl.isNullOrBlank()) {
                                    AsyncImage(
                                        model = setting.qrisImageUrl,
                                        contentDescription = "QRIS ${setting.storeName}",
                                        modifier = Modifier.size(190.dp).clip(RoundedCornerShape(14.dp)).background(Color.White),
                                        contentScale = ContentScale.Fit,
                                    )
                                } else {
                                    Box(Modifier.size(190.dp).background(Color.White, RoundedCornerShape(14.dp)), contentAlignment = Alignment.Center) {
                                        Text("QRIS belum diupload", color = setting.secondaryTextColor)
                                    }
                                }
                                Text("Tunjukkan QRIS ini ke pembeli, lalu tekan tombol di bawah setelah pembayaran masuk.", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                            }
                        }
                    }
                }
                error?.let {
                    item { Text(it, color = Color(0xFFDC2626), style = MaterialTheme.typography.bodySmall) }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = { onSubmit(method, paidAmount) },
                enabled = canSubmit,
                colors = ButtonDefaults.buttonColors(
                    containerColor = setting.themeColor,
                    contentColor = bestContentColor(setting.themeColor),
                    disabledContainerColor = setting.themeColor.copy(alpha = 0.32f),
                    disabledContentColor = bestContentColor(setting.themeColor).copy(alpha = 0.72f),
                ),
            ) {
                if (loading) {
                    CircularProgressIndicator(modifier = Modifier.size(16.dp), strokeWidth = 2.dp, color = bestContentColor(setting.themeColor))
                    Spacer(Modifier.width(8.dp))
                }
                Text(if (method == "qris") "Pembayaran Masuk" else "Selesaikan")
            }
        },
        dismissButton = {
            TextButton(
                onClick = onDismiss,
                enabled = !loading,
                colors = ButtonDefaults.textButtonColors(contentColor = setting.secondaryTextColor),
            ) {
                Text("Batal")
            }
        },
    )
}

@Composable
private fun PaymentMethodButton(label: String, selected: Boolean, accent: Color, modifier: Modifier = Modifier, onClick: () -> Unit) {
    OutlinedButton(
        onClick = onClick,
        modifier = modifier,
        shape = RoundedCornerShape(999.dp),
        colors = ButtonDefaults.outlinedButtonColors(
            containerColor = if (selected) accent else Color.White,
            contentColor = if (selected) bestContentColor(accent) else Color(0xFF0F172A),
        ),
        border = BorderStroke(1.dp, if (selected) accent else Color(0xFFE2E8F0)),
    ) {
        Text(label, fontWeight = FontWeight.Bold)
    }
}

@Composable
private fun ReceiptDialog(setting: StoreSetting, sale: SaleReceipt, onDone: () -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var printMessage by remember { mutableStateOf<String?>(null) }
    var printing by remember { mutableStateOf(false) }

    fun printDefault() {
        val printer = PrinterStore.load(context)
        if (printer == null) {
            printMessage = "Printer belum dipilih. Buka ikon printer di header POS untuk pairing/test printer."
            return
        }

        printing = true
        printMessage = "Mengirim struk ke ${printer.name}..."
        scope.launch {
            try {
                BluetoothReceiptPrinter.print(context, printer.address, setting, sale)
                printMessage = "Struk terkirim ke ${printer.name}."
            } catch (error: Exception) {
                printMessage = error.message ?: "Gagal mencetak struk."
            } finally {
                printing = false
            }
        }
    }

    val bluetoothPermissionLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { grants ->
        val granted = bluetoothPrintPermissions().all { grants[it] == true || ContextCompat.checkSelfPermission(context, it) == PackageManager.PERMISSION_GRANTED }
        if (granted) {
            printDefault()
        } else {
            printMessage = "Izin Bluetooth dibutuhkan untuk mencetak struk."
        }
    }

    fun requestPrint() {
        val missing = bluetoothPrintPermissions()
            .filter { ContextCompat.checkSelfPermission(context, it) != PackageManager.PERMISSION_GRANTED }

        if (missing.isNotEmpty()) {
            bluetoothPermissionLauncher.launch(missing.toTypedArray())
        } else {
            printDefault()
        }
    }

    AlertDialog(
        onDismissRequest = onDone,
        containerColor = Color.White,
        titleContentColor = setting.textColor,
        textContentColor = setting.textColor,
        title = { Text("Transaksi selesai", color = setting.textColor, fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text(sale.invoiceNumber, color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                sale.items.forEach { item ->
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("${formatQuantity(item.quantity)} ${item.unit} x ${item.name}", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall, modifier = Modifier.weight(1f))
                        Text(formatMoney(item.lineTotal, setting.currency), color = setting.priceTextColor, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
                    }
                }
                SummaryLine("Total", sale.grandTotal, setting, setting.textColor, setting.priceTextColor, true)
                SummaryLine("Dibayar (${sale.paymentMethod.uppercase(Locale.US)})", sale.paidAmount, setting, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Kembalian", sale.changeAmount, setting, setting.secondaryTextColor, setting.priceTextColor)
                Text(setting.receiptFooter, color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                printMessage?.let {
                    Text(it, color = setting.themeColor, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
                }
            }
        },
        confirmButton = {
            Button(
                onClick = { requestPrint() },
                enabled = !printing,
                colors = ButtonDefaults.buttonColors(containerColor = setting.themeColor, contentColor = bestContentColor(setting.themeColor)),
            ) {
                if (printing) {
                    CircularProgressIndicator(modifier = Modifier.size(16.dp), strokeWidth = 2.dp, color = bestContentColor(setting.themeColor))
                    Spacer(Modifier.width(8.dp))
                }
                Text(if (printing) "Mencetak..." else "Cetak Struk")
            }
        },
        dismissButton = {
            TextButton(
                onClick = onDone,
                colors = ButtonDefaults.textButtonColors(contentColor = setting.secondaryTextColor),
            ) {
                Text("Selesai")
            }
        },
    )
}

@Composable
private fun PrinterSetupDialog(setting: StoreSetting, onDismiss: () -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var printers by remember { mutableStateOf<List<PrinterDevice>>(emptyList()) }
    var selectedPrinter by remember { mutableStateOf(PrinterStore.load(context)) }
    var scanning by remember { mutableStateOf(false) }
    var testing by remember { mutableStateOf(false) }
    var message by remember { mutableStateOf<String?>(null) }
    var scanReceiver by remember { mutableStateOf<BroadcastReceiver?>(null) }

    fun stopScan() {
        runCatching { BluetoothReceiptPrinter.stopScan(context) }
        scanReceiver?.let { receiver ->
            runCatching { context.unregisterReceiver(receiver) }
        }
        scanReceiver = null
        scanning = false
    }

    fun loadPairedPrinters() {
        runCatching {
            printers = BluetoothReceiptPrinter.pairedPrinters(context)
            message = if (printers.isEmpty()) "Belum ada printer paired. Tekan Cari Printer." else null
        }.onFailure {
            message = it.message ?: "Gagal membaca daftar printer."
        }
    }

    fun startScan() {
        stopScan()
        runCatching {
            val receiver = BluetoothReceiptPrinter.startScan(
                context = context,
                onFound = { found ->
                    printers = (printers + found)
                        .distinctBy { it.address }
                        .sortedWith(compareByDescending<PrinterDevice> { it.bonded }.thenBy { it.name.lowercase(Locale.US) })
                    message = null
                },
                onFinished = {
                    scanning = false
                    message = if (printers.isEmpty()) "Printer belum ditemukan. Pastikan printer menyala dan mode discoverable." else null
                },
            )
            scanReceiver = receiver
            scanning = true
            message = "Mencari printer Bluetooth..."
        }.onFailure {
            scanning = false
            message = it.message ?: "Gagal scan printer."
        }
    }

    val permissionLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { grants ->
        val granted = bluetoothRuntimePermissions().all { grants[it] == true || ContextCompat.checkSelfPermission(context, it) == PackageManager.PERMISSION_GRANTED }
        if (granted) {
            loadPairedPrinters()
        } else {
            message = "Izin Bluetooth/Location dibutuhkan untuk scan dan test printer."
        }
    }

    fun ensurePermissionThen(action: () -> Unit) {
        val missing = bluetoothRuntimePermissions()
            .filter { ContextCompat.checkSelfPermission(context, it) != PackageManager.PERMISSION_GRANTED }

        if (missing.isEmpty()) {
            action()
        } else {
            permissionLauncher.launch(missing.toTypedArray())
        }
    }

    fun selectPrinter(device: PrinterDevice) {
        PrinterStore.save(context, device)
        selectedPrinter = device
        message = "Printer aktif: ${device.name}. Tombol Cetak Struk akan memakai printer ini."
    }

    fun testPrint(device: PrinterDevice) {
        stopScan()
        testing = true
        message = "Mengirim test print ke ${device.name}..."
        scope.launch {
            try {
                BluetoothReceiptPrinter.printTest(context, device.address, setting)
                PrinterStore.save(context, device)
                selectedPrinter = device
                message = "Test print terkirim. Printer aktif: ${device.name}."
            } catch (error: Exception) {
                message = error.message ?: "Test print gagal."
            } finally {
                testing = false
            }
        }
    }

    LaunchedEffect(Unit) {
        ensurePermissionThen { loadPairedPrinters() }
    }

    DisposableEffect(Unit) {
        onDispose { stopScan() }
    }

    AlertDialog(
        onDismissRequest = {
            if (!testing) {
                stopScan()
                onDismiss()
            }
        },
        containerColor = Color.White,
        titleContentColor = setting.textColor,
        textContentColor = setting.textColor,
        title = { Text("Printer Struk", color = setting.textColor, fontWeight = FontWeight.Bold) },
        text = {
            Column(
                modifier = Modifier.widthIn(max = 560.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                Text("Pairing printer sekali di sini. Setelah aktif, tombol Cetak Struk akan langsung memakai printer ini.", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                selectedPrinter?.let { device ->
                    Card(
                        colors = CardDefaults.cardColors(containerColor = setting.themeColor.copy(alpha = 0.09f)),
                        shape = RoundedCornerShape(16.dp),
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
                            Box(
                                modifier = Modifier.size(42.dp).background(setting.themeColor.copy(alpha = 0.12f), RoundedCornerShape(12.dp)),
                                contentAlignment = Alignment.Center,
                            ) {
                                PrinterGlyph(setting.themeColor)
                            }
                            Spacer(Modifier.width(12.dp))
                            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                                Text("Printer aktif", color = setting.themeColor, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodySmall)
                                Text(device.name, color = setting.textColor, fontWeight = FontWeight.SemiBold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                                Text(device.address, color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                            }
                        }
                    }
                } ?: Surface(color = Color(0xFFF8FAFC), shape = RoundedCornerShape(16.dp), border = BorderStroke(1.dp, Color(0xFFE2E8F0))) {
                    Text("Belum ada printer aktif. Tekan Cari Printer, pilih perangkat, lalu Test untuk memastikan koneksi.", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(14.dp))
                }
                Button(
                    onClick = { ensurePermissionThen { startScan() } },
                    enabled = !scanning && !testing,
                    colors = ButtonDefaults.buttonColors(containerColor = setting.themeColor, contentColor = bestContentColor(setting.themeColor)),
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    if (scanning) {
                        CircularProgressIndicator(modifier = Modifier.size(16.dp), strokeWidth = 2.dp, color = bestContentColor(setting.themeColor))
                        Spacer(Modifier.width(8.dp))
                    }
                    Text(if (scanning) "Mencari Printer..." else "Cari Printer Bluetooth")
                }
                message?.let {
                    val isError = it.contains("gagal", ignoreCase = true) ||
                        it.contains("izin", ignoreCase = true) ||
                        it.contains("Bluetooth belum aktif", ignoreCase = true)
                    Surface(
                        color = if (isError) Color(0xFFFEF2F2) else setting.themeColor.copy(alpha = 0.08f),
                        shape = RoundedCornerShape(12.dp),
                        border = BorderStroke(1.dp, if (isError) Color(0xFFFECACA) else setting.themeColor.copy(alpha = 0.18f)),
                    ) {
                        Text(
                            it,
                            color = if (isError) Color(0xFFB91C1C) else setting.themeColor,
                            style = MaterialTheme.typography.bodySmall,
                            fontWeight = FontWeight.SemiBold,
                            modifier = Modifier.padding(10.dp),
                        )
                    }
                }
                if (printers.isEmpty()) {
                    Surface(color = Color(0xFFF8FAFC), shape = RoundedCornerShape(14.dp), border = BorderStroke(1.dp, Color(0xFFE2E8F0))) {
                        Text("Daftar printer masih kosong. Jika printer tidak bisa pair dari Settings, nyalakan printer lalu gunakan scan dari menu ini.", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(12.dp))
                    }
                } else {
                    LazyColumn(modifier = Modifier.heightIn(max = 320.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        items(printers) { device ->
                            Card(
                                colors = CardDefaults.cardColors(containerColor = if (selectedPrinter?.address == device.address) setting.themeColor.copy(alpha = 0.07f) else Color(0xFFF8FAFC)),
                                shape = RoundedCornerShape(14.dp),
                                modifier = Modifier.fillMaxWidth(),
                            ) {
                                Row(Modifier.fillMaxWidth().padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                                    Column(Modifier.weight(1f)) {
                                        Text(device.name, color = setting.textColor, fontWeight = FontWeight.SemiBold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                                        Text(
                                            if (selectedPrinter?.address == device.address) "Aktif" else if (device.bonded) "Paired" else "Ditemukan",
                                            color = setting.themeColor,
                                            style = MaterialTheme.typography.bodySmall,
                                            fontWeight = if (selectedPrinter?.address == device.address) FontWeight.Bold else FontWeight.Normal,
                                        )
                                        Text(device.address, color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                                    }
                                    OutlinedButton(
                                        onClick = { selectPrinter(device) },
                                        enabled = !testing && !scanning && selectedPrinter?.address != device.address,
                                        shape = RoundedCornerShape(12.dp),
                                        border = BorderStroke(1.dp, setting.themeColor.copy(alpha = 0.45f)),
                                        colors = ButtonDefaults.outlinedButtonColors(contentColor = setting.themeColor),
                                    ) {
                                        Text(if (selectedPrinter?.address == device.address) "Aktif" else "Pilih")
                                    }
                                    Spacer(Modifier.width(8.dp))
                                    Button(
                                        onClick = { testPrint(device) },
                                        enabled = !testing && !scanning,
                                        shape = RoundedCornerShape(12.dp),
                                        colors = ButtonDefaults.buttonColors(containerColor = setting.themeColor, contentColor = bestContentColor(setting.themeColor)),
                                    ) {
                                        Text("Test")
                                    }
                                }
                            }
                        }
                    }
                }
            }
        },
        confirmButton = {},
        dismissButton = {
            TextButton(
                onClick = {
                    stopScan()
                    onDismiss()
                },
                enabled = !testing,
                colors = ButtonDefaults.textButtonColors(contentColor = setting.secondaryTextColor),
            ) {
                Text("Tutup")
            }
        },
    )
}

@Composable
private fun SummaryLine(label: String, value: Number, setting: StoreSetting, labelColor: Color, valueColor: Color, bold: Boolean = false) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, color = labelColor, style = MaterialTheme.typography.bodySmall)
        Text(formatMoney(value, setting.currency), color = valueColor, fontWeight = if (bold) FontWeight.Bold else FontWeight.Normal)
    }
}

@Composable
private fun EmptyCatalog(
    modifier: Modifier = Modifier,
    title: String = "Belum ada item",
    subtitle: String = "Tambahkan produk aktif dari web admin.",
) {
    Box(modifier, contentAlignment = Alignment.Center) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(title, fontWeight = FontWeight.Bold)
            Text(subtitle, color = Color(0xFF64748B))
        }
    }
}

@Composable
private fun ErrorState(message: String, onRetry: () -> Unit, modifier: Modifier = Modifier) {
    Column(modifier.padding(24.dp), horizontalAlignment = Alignment.CenterHorizontally) {
        Text("Sync gagal", fontWeight = FontWeight.Bold)
        Text(message, color = Color(0xFF64748B), modifier = Modifier.padding(vertical = 8.dp))
        Button(onClick = onRetry) { Text("Coba lagi") }
    }
}

private object SessionStore {
    private const val PREF = "arventa_session"

    fun load(context: Context): PairingSession? {
        val pref = context.getSharedPreferences(PREF, Context.MODE_PRIVATE)
        val baseUrl = pref.getString("base_url", null) ?: return null
        val token = pref.getString("token", null) ?: return null
        val cashierName = pref.getString("cashier_name", "Kasir") ?: "Kasir"
        return PairingSession(baseUrl, token, cashierName)
    }

    fun save(context: Context, session: PairingSession) {
        context.getSharedPreferences(PREF, Context.MODE_PRIVATE).edit()
            .putString("base_url", session.baseUrl)
            .putString("token", session.token)
            .putString("cashier_name", session.cashierName)
            .apply()
    }

    fun clear(context: Context) {
        context.getSharedPreferences(PREF, Context.MODE_PRIVATE).edit().clear().apply()
    }
}

private object PosRepository {
    suspend fun pair(baseUrlInput: String, pairingInput: String): PairingSession = withContext(Dispatchers.IO) {
        val parsed = parsePairingInput(baseUrlInput, pairingInput)
        val body = JSONObject()
            .put("code", parsed.first)
            .put("device_name", Build.MODEL ?: "Android Cashier")
            .put("device_uid", "${Build.MANUFACTURER}-${Build.MODEL}-${Build.ID}")

        val response = request("${parsed.second}/api/pairing/connect", "POST", body.toString(), null)
        val json = JSONObject(response)
        val cashier = json.optJSONObject("cashier")
        val connectedBaseUrl = json.optString("base_url")
            .ifBlank { parsed.second }
        PairingSession(
            baseUrl = normalizeBaseUrl(connectedBaseUrl),
            token = json.getString("token"),
            cashierName = cashier?.optString("name")?.takeIf { it.isNotBlank() } ?: "Kasir",
        )
    }

    suspend fun logout(session: PairingSession) = withContext(Dispatchers.IO) {
        runCatching {
            request("${session.baseUrl}/api/logout", "POST", "{}", session.token)
        }
    }

    suspend fun sync(session: PairingSession): PosState = withContext(Dispatchers.IO) {
        val json = JSONObject(request("${session.baseUrl}/api/sync", "GET", null, session.token))
        val store = json.getJSONObject("store")
        val products = json.getJSONArray("products")
        val items = buildList {
            for (index in 0 until products.length()) {
                val item = products.getJSONObject(index)
                add(
                    PosItem(
                        id = item.getInt("id"),
                        name = item.getString("name"),
                        sku = item.optString("sku").takeIf { it.isNotBlank() && it != "null" },
                        type = item.optString("type", "product"),
                        unit = item.optString("unit", "pcs"),
                        price = item.optDouble("price", 0.0).roundToInt(),
                        stock = if (item.isNull("stock")) null else item.optDouble("stock"),
                        freeQuantity = if (item.isNull("free_quantity")) null else item.optDouble("free_quantity"),
                        imageUrl = item.optString("image_url").takeIf { it.isNotBlank() && it != "null" },
                    )
                )
            }
        }
        PosState(setting = store.toSetting(session.baseUrl), items = items, loading = false)
    }

    suspend fun checkout(session: PairingSession, lines: List<CheckoutLine>, paidAmount: Double, paymentMethod: String): SaleReceipt = withContext(Dispatchers.IO) {
        val items = org.json.JSONArray()
        lines.forEach { line ->
            val item = JSONObject()
                .put("quantity", line.quantity)

            if (line.productId != null) {
                item.put("product_id", line.productId)
            } else {
                item
                    .put("name", line.name)
                    .put("unit", line.unit)
                    .put("unit_price", line.unitPrice)
            }

            items.put(item)
        }
        val body = JSONObject()
            .put("items", items)
            .put("paid_amount", paidAmount)
            .put("payment_method", paymentMethod)

        val response = request("${session.baseUrl}/api/transactions", "POST", body.toString(), session.token)
        val sale = JSONObject(response).getJSONObject("sale")
        val saleItems = sale.getJSONArray("items")
        val receiptItems = buildList {
            for (index in 0 until saleItems.length()) {
                val item = saleItems.getJSONObject(index)
                add(
                    SaleReceiptItem(
                        name = item.getString("name"),
                        unit = item.optString("unit", "pcs"),
                        quantity = item.optDouble("quantity", 0.0),
                        lineTotal = item.optDouble("line_total", 0.0),
                    )
                )
            }
        }

        SaleReceipt(
            invoiceNumber = sale.getString("invoice_number"),
            subtotal = sale.optDouble("subtotal", 0.0),
            taxTotal = sale.optDouble("tax_total", 0.0),
            serviceTotal = sale.optDouble("service_charge_total", 0.0),
            grandTotal = sale.optDouble("grand_total", 0.0),
            paidAmount = sale.optDouble("paid_amount", 0.0),
            changeAmount = sale.optDouble("change_amount", 0.0),
            paymentMethod = sale.optString("payment_method", paymentMethod),
            items = receiptItems,
        )
    }

    private fun parsePairingInput(baseUrlInput: String, pairingInput: String): Pair<String, String> {
        val trimmed = pairingInput.trim()
        if (trimmed.startsWith("{")) {
            val json = JSONObject(trimmed)
            val code = json.getString("code").filter { it.isDigit() }
            val apiUrl = json.optString("api_url")
            val baseUrlFromApi = apiUrl
                .takeIf { it.isNotBlank() }
                ?.substringBefore("/api/pairing/connect")
                .orEmpty()
            val baseUrl = json.optString("base_url")
                .ifBlank { baseUrlFromApi }
                .ifBlank { baseUrlInput }

            if (code.isBlank()) {
                throw IllegalStateException("Kode pairing tidak ditemukan di QR.")
            }

            return code to normalizeBaseUrl(baseUrl)
        }

        val code = trimmed.filter { it.isDigit() }
        if (code.isBlank()) {
            throw IllegalStateException("Kode pairing tidak valid.")
        }

        val baseUrl = baseUrlInput.ifBlank { BuildConfig.ARVENTA_PAIRING_BASE_URL }

        return code to normalizeBaseUrl(baseUrl)
    }

    private fun normalizeBaseUrl(value: String): String {
        val baseUrl = value.trim().trimEnd('/')

        if (baseUrl.isBlank()) {
            throw IllegalStateException("Base URL tidak ditemukan. Scan QR pairing dari Web Admin tenant.")
        }

        return if (baseUrl.startsWith("http://") || baseUrl.startsWith("https://")) {
            baseUrl
        } else {
            "https://$baseUrl"
        }
    }

    private fun JSONObject.toSetting(baseUrl: String): StoreSetting {
        return StoreSetting(
            storeName = cleanString("store_name", "Arventa POS"),
            businessType = cleanString("business_type", "Retail"),
            address = cleanString("address"),
            logoUrl = optString("logo_url").takeIf { it.isNotBlank() && it != "null" }?.let { absoluteUrl(baseUrl, it) },
            qrisImageUrl = optString("qris_image_url").takeIf { it.isNotBlank() && it != "null" }?.let { absoluteUrl(baseUrl, it) },
            receiptQrImageUrl = optString("receipt_qr_image_url").takeIf { it.isNotBlank() && it != "null" }?.let { absoluteUrl(baseUrl, it) },
            themeColor = parseColor(optString("theme_color", "#2563EB")),
            textColor = parseColor(optString("app_text_color", "#0F172A")),
            secondaryTextColor = parseColor(optString("app_secondary_text_color", "#64748B")),
            priceTextColor = parseColor(optString("app_price_text_color", "#0F172A")),
            appLayout = optString("app_layout", "grid"),
            productCardStyle = optString("product_card_style", "minimal"),
            posOrientation = optString("pos_orientation", "portrait"),
            showSku = optBoolean("show_sku_on_app", false),
            showStock = optBoolean("show_stock_on_app", true),
            showSearch = optBoolean("show_search_on_app", true),
            showCart = optBoolean("show_cart_on_app", true),
            cartPosition = optString("cart_position", "bottom"),
            checkoutPosition = optString("checkout_position", "bottom"),
            showOrderSummary = optBoolean("show_order_summary_on_app", true),
            taxRate = optDouble("tax_rate", 0.0),
            serviceChargeRate = optDouble("service_charge_rate", 0.0),
            currency = cleanString("currency", "IDR").uppercase(Locale.US),
            receiptFooter = cleanString("receipt_footer", "Terima kasih."),
            receiptHeaderTitle = cleanString("receipt_header_title"),
            receiptHeaderSubtitle = cleanString("receipt_header_subtitle"),
            receiptHeaderNotes = cleanString("receipt_header_notes"),
            receiptHeaderAlignment = cleanString("receipt_header_alignment", "center"),
            receiptTemplate = cleanString("receipt_template", "classic"),
            receiptPaperSize = cleanString("receipt_paper_size", "58"),
            receiptShowLogo = optBoolean("receipt_show_logo", false),
            receiptShowStoreName = optBoolean("receipt_show_store_name", true),
            receiptShowAddress = optBoolean("receipt_show_address", true),
            receiptShowDatetime = optBoolean("receipt_show_datetime", true),
            receiptShowQris = optBoolean("receipt_show_qris", false),
            receiptShowBusinessType = optBoolean("receipt_show_business_type", true),
            receiptShowPaymentMethod = optBoolean("receipt_show_payment_method", true),
            receiptShowItemPrice = optBoolean("receipt_show_item_price", true),
        )
    }

    private fun JSONObject.cleanString(key: String, fallback: String = ""): String {
        if (!has(key) || isNull(key)) return fallback

        val value = optString(key, fallback).trim()
        return if (value.equals("null", ignoreCase = true)) fallback else value
    }

    private fun absoluteUrl(baseUrl: String, value: String): String {
        if (value.startsWith("http://") || value.startsWith("https://")) {
            return value
        }
        return "${baseUrl.trimEnd('/')}/${value.trimStart('/')}"
    }

    private fun request(url: String, method: String, body: String?, token: String?): String {
        val connection = URL(url).openConnection() as HttpURLConnection
        connection.requestMethod = method
        connection.connectTimeout = 15_000
        connection.readTimeout = 15_000
        connection.setRequestProperty("Accept", "application/json")
        if (token != null) connection.setRequestProperty("Authorization", "Bearer $token")
        if (body != null) {
            connection.doOutput = true
            connection.setRequestProperty("Content-Type", "application/json")
            OutputStreamWriter(connection.outputStream).use { it.write(body) }
        }

        val stream = if (connection.responseCode in 200..299) connection.inputStream else connection.errorStream
        val text = stream.bufferedReader().use(BufferedReader::readText)
        if (connection.responseCode !in 200..299) {
            throw IllegalStateException(extractMessage(text))
        }
        return text
    }

    private fun extractMessage(text: String): String {
        return runCatching {
            val json = JSONObject(text)
            val errors = json.optJSONObject("errors")
            if (errors != null) {
                val key = errors.keys().asSequence().first()
                errors.getJSONArray(key).getString(0)
            } else {
                json.optString("message", "Request gagal.")
            }
        }.getOrElse { "Request gagal." }
    }
}

private object BluetoothReceiptPrinter {
    private val sppUuid: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")
    private val charset: Charset = Charset.forName("CP437")

    @SuppressLint("MissingPermission")
    fun pairedPrinters(context: Context): List<PrinterDevice> {
        ensureBluetoothPermission(context)
        val adapter = BluetoothAdapter.getDefaultAdapter()
            ?: throw IllegalStateException("Bluetooth tidak tersedia di perangkat ini.")

        if (!adapter.isEnabled) {
            throw IllegalStateException("Bluetooth belum aktif.")
        }

        return adapter.bondedDevices
            .map { device ->
                PrinterDevice(
                    name = device.name?.takeIf { it.isNotBlank() } ?: "Printer Bluetooth",
                    address = device.address,
                    bonded = true,
                )
            }
            .sortedBy { it.name.lowercase(Locale.US) }
    }

    @SuppressLint("MissingPermission")
    fun startScan(context: Context, onFound: (PrinterDevice) -> Unit, onFinished: () -> Unit): BroadcastReceiver {
        ensureScanPermission(context)
        val adapter = BluetoothAdapter.getDefaultAdapter()
            ?: throw IllegalStateException("Bluetooth tidak tersedia di perangkat ini.")

        if (!adapter.isEnabled) {
            throw IllegalStateException("Bluetooth belum aktif.")
        }

        val receiver = object : BroadcastReceiver() {
            override fun onReceive(receiverContext: Context, intent: Intent) {
                when (intent.action) {
                    BluetoothDevice.ACTION_FOUND -> {
                        val device = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                            intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE, BluetoothDevice::class.java)
                        } else {
                            @Suppress("DEPRECATION")
                            intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE)
                        }

                        if (device != null) {
                            onFound(
                                PrinterDevice(
                                    name = device.name?.takeIf { it.isNotBlank() } ?: "Printer Bluetooth",
                                    address = device.address,
                                    bonded = device.bondState == BluetoothDevice.BOND_BONDED,
                                ),
                            )
                        }
                    }

                    BluetoothAdapter.ACTION_DISCOVERY_FINISHED -> onFinished()
                }
            }
        }

        val filter = IntentFilter().apply {
            addAction(BluetoothDevice.ACTION_FOUND)
            addAction(BluetoothAdapter.ACTION_DISCOVERY_FINISHED)
        }
        ContextCompat.registerReceiver(context, receiver, filter, ContextCompat.RECEIVER_NOT_EXPORTED)

        if (adapter.isDiscovering) {
            adapter.cancelDiscovery()
        }

        if (!adapter.startDiscovery()) {
            runCatching { context.unregisterReceiver(receiver) }
            throw IllegalStateException("Gagal memulai scan Bluetooth.")
        }

        return receiver
    }

    @SuppressLint("MissingPermission")
    fun stopScan(context: Context) {
        if (hasScanPermission(context)) {
            BluetoothAdapter.getDefaultAdapter()?.takeIf { it.isDiscovering }?.cancelDiscovery()
        }
    }

    @SuppressLint("MissingPermission")
    suspend fun print(context: Context, address: String, setting: StoreSetting, sale: SaleReceipt) = withContext(Dispatchers.IO) {
        ensureBluetoothPermission(context)
        val adapter = BluetoothAdapter.getDefaultAdapter()
            ?: throw IllegalStateException("Bluetooth tidak tersedia di perangkat ini.")

        if (!adapter.isEnabled) {
            throw IllegalStateException("Bluetooth belum aktif.")
        }

        val device = adapter.getRemoteDevice(address)
        adapter.cancelDiscovery()

        device.createRfcommSocketToServiceRecord(sppUuid).use { socket ->
            socket.connect()
            socket.outputStream.use { output ->
                output.write(buildReceipt(setting, sale))
                output.flush()
            }
        }
    }

    suspend fun printTest(context: Context, address: String, setting: StoreSetting) {
        val sale = SaleReceipt(
            invoiceNumber = "TEST-PRINT",
            subtotal = 1000.0,
            taxTotal = 0.0,
            serviceTotal = 0.0,
            grandTotal = 1000.0,
            paidAmount = 1000.0,
            changeAmount = 0.0,
            paymentMethod = "test",
            items = listOf(
                SaleReceiptItem(
                    name = "Test Printer",
                    unit = "pcs",
                    quantity = 1.0,
                    lineTotal = 1000.0,
                ),
            ),
        )

        print(context, address, setting, sale)
    }

    private fun ensureBluetoothPermission(context: Context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S &&
            ContextCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED
        ) {
            throw IllegalStateException("Izin Bluetooth belum diberikan.")
        }
    }

    private fun ensureScanPermission(context: Context) {
        val missing = bluetoothRuntimePermissions()
            .filter { ContextCompat.checkSelfPermission(context, it) != PackageManager.PERMISSION_GRANTED }

        if (missing.isNotEmpty()) {
            throw IllegalStateException("Izin Bluetooth scan belum diberikan.")
        }
    }

    private fun hasScanPermission(context: Context): Boolean {
        return bluetoothRuntimePermissions()
            .all { ContextCompat.checkSelfPermission(context, it) == PackageManager.PERMISSION_GRANTED }
    }

    private fun buildReceipt(setting: StoreSetting, sale: SaleReceipt): ByteArray {
        val buffer = ByteArrayOutputStream()
        val width = receiptWidth(setting)
        val compact = setting.receiptTemplate == "compact"
        val detailed = setting.receiptTemplate == "detailed"
        fun command(vararg bytes: Int) {
            buffer.write(bytes.map { it.toByte() }.toByteArray())
        }
        fun text(value: String = "") {
            buffer.write(sanitizeReceiptText(value).toByteArray(charset))
            buffer.write('\n'.code)
        }

        command(0x1B, 0x40)
        command(0x1B, 0x61, 0x01)
        if (setting.receiptShowLogo && !setting.logoUrl.isNullOrBlank()) {
            writeImage(buffer, setting.logoUrl, maxWidth = if (width == 48) 220 else 160)
            text()
        }
        command(0x1B, 0x61, receiptAlignmentCode(setting.receiptHeaderAlignment))
        command(0x1B, 0x21, 0x08)
        val headerTitle = setting.receiptHeaderTitle.ifBlank {
            if (setting.receiptShowStoreName) setting.storeName else ""
        }
        if (headerTitle.isNotBlank()) {
            wrapReceiptLine(headerTitle, width).forEach(::text)
        }
        command(0x1B, 0x21, 0x00)
        if (setting.receiptShowStoreName && setting.receiptHeaderTitle.isNotBlank()) {
            wrapReceiptLine(setting.storeName, width).forEach(::text)
        }
        if (setting.receiptHeaderSubtitle.isNotBlank()) {
            wrapReceiptLine(setting.receiptHeaderSubtitle, width).forEach(::text)
        }
        if (setting.receiptShowBusinessType && !compact) {
            text(setting.businessType)
        }
        if (setting.receiptShowAddress && setting.address.isNotBlank()) {
            wrapReceiptLine(setting.address, width).forEach(::text)
        }
        setting.receiptHeaderNotes
            .lineSequence()
            .map { it.trim() }
            .filter { it.isNotBlank() }
            .forEach { line -> wrapReceiptLine(line, width).forEach(::text) }
        text(line(width))
        command(0x1B, 0x61, 0x00)
        text("Invoice: ${sale.invoiceNumber}")
        if (setting.receiptShowDatetime) {
            val now = Date()
            text("Tanggal: ${SimpleDateFormat("dd/MM/yyyy", Locale("id", "ID")).format(now)}")
            text("Jam: ${SimpleDateFormat("HH:mm", Locale("id", "ID")).format(now)}")
        }
        if (setting.receiptShowPaymentMethod && !compact) {
            text("Pembayaran: ${sale.paymentMethod.uppercase(Locale.US)}")
        }
        text(line(width))

        sale.items.forEach { item ->
            wrapReceiptLine("${formatQuantity(item.quantity)} ${item.unit} x ${item.name}", width).forEach(::text)
            if (setting.receiptShowItemPrice) {
                val label = if (detailed) "  Subtotal" else ""
                text(twoColumn(label, formatMoney(item.lineTotal, setting.currency), width))
            }
        }

        text(line(width))
        if (!compact) {
            text(twoColumn("Subtotal", formatMoney(sale.subtotal, setting.currency), width))
            if (sale.taxTotal > 0.0) text(twoColumn("Pajak", formatMoney(sale.taxTotal, setting.currency), width))
            if (sale.serviceTotal > 0.0) text(twoColumn("Service", formatMoney(sale.serviceTotal, setting.currency), width))
        }
        text(twoColumn("Total", formatMoney(sale.grandTotal, setting.currency), width))
        text(twoColumn("Dibayar", formatMoney(sale.paidAmount, setting.currency), width))
        text(twoColumn("Kembali", formatMoney(sale.changeAmount, setting.currency), width))
        if (setting.receiptShowQris && !setting.receiptQrImageUrl.isNullOrBlank()) {
            text(line(width))
            command(0x1B, 0x61, 0x01)
            text("QR")
            writeImage(buffer, setting.receiptQrImageUrl, maxWidth = if (width == 48) 260 else 220)
            command(0x1B, 0x61, 0x00)
        }
        text(line(width))
        command(0x1B, 0x61, 0x01)
        wrapReceiptLine(setting.receiptFooter.ifBlank { "Terima kasih." }, width).forEach(::text)
        text()
        text()
        command(0x1D, 0x56, 0x42, 0x00)

        return buffer.toByteArray()
    }

    private fun writeImage(buffer: ByteArrayOutputStream, imageUrl: String, maxWidth: Int) {
        val bitmap = loadReceiptBitmap(imageUrl, maxWidth) ?: return
        val widthBytes = (bitmap.width + 7) / 8
        val data = ByteArray(widthBytes * bitmap.height)

        for (y in 0 until bitmap.height) {
            for (x in 0 until bitmap.width) {
                val pixel = bitmap.getPixel(x, y)
                val red = android.graphics.Color.red(pixel)
                val green = android.graphics.Color.green(pixel)
                val blue = android.graphics.Color.blue(pixel)
                val alpha = android.graphics.Color.alpha(pixel)
                val luminance = (red * 0.299 + green * 0.587 + blue * 0.114).toInt()

                if (alpha > 80 && luminance < 170) {
                    val index = y * widthBytes + x / 8
                    data[index] = (data[index].toInt() or (0x80 shr (x % 8))).toByte()
                }
            }
        }

        buffer.write(byteArrayOf(0x1D, 0x76, 0x30, 0x00))
        buffer.write(byteArrayOf((widthBytes % 256).toByte(), (widthBytes / 256).toByte()))
        buffer.write(byteArrayOf((bitmap.height % 256).toByte(), (bitmap.height / 256).toByte()))
        buffer.write(data)
        buffer.write('\n'.code)
    }

    private fun loadReceiptBitmap(imageUrl: String, maxWidth: Int): Bitmap? {
        return runCatching {
            URL(imageUrl).openStream().use { stream ->
                val original = BitmapFactory.decodeStream(stream) ?: return null
                val width = original.width.coerceAtMost(maxWidth)
                val height = (original.height * (width.toFloat() / original.width)).roundToInt().coerceAtLeast(1)
                Bitmap.createScaledBitmap(original, width, height, true)
            }
        }.getOrNull()
    }

    private fun sanitizeReceiptText(value: String): String {
        return value
            .replace("–", "-")
            .replace("—", "-")
            .replace("’", "'")
            .replace("“", "\"")
            .replace("”", "\"")
    }

    private fun receiptWidth(setting: StoreSetting): Int = if (setting.receiptPaperSize == "80") 48 else 32

    private fun receiptAlignmentCode(value: String): Int {
        return when (value.lowercase(Locale.US)) {
            "left" -> 0x00
            "right" -> 0x02
            else -> 0x01
        }
    }

    private fun line(width: Int): String = "-".repeat(width)

    private fun twoColumn(left: String, right: String, width: Int): String {
        val cleanLeft = sanitizeReceiptText(left).take(width)
        val cleanRight = sanitizeReceiptText(right).take(width)
        val spaces = (width - cleanLeft.length - cleanRight.length).coerceAtLeast(1)
        return cleanLeft + " ".repeat(spaces) + cleanRight
    }

    private fun wrapReceiptLine(value: String, width: Int): List<String> {
        val words = sanitizeReceiptText(value).split(" ")
        val lines = mutableListOf<String>()
        var current = ""

        words.forEach { word ->
            current = when {
                current.isBlank() -> word.take(width)
                current.length + 1 + word.length <= width -> "$current $word"
                else -> {
                    lines += current
                    word.take(width)
                }
            }
        }

        if (current.isNotBlank()) lines += current
        return lines.ifEmpty { listOf(value.take(width)) }
    }
}

private fun normalizeBaseUrl(value: String): String = value.trim().trimEnd('/')

private fun bluetoothRuntimePermissions(): List<String> {
    return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
        listOf(Manifest.permission.BLUETOOTH_CONNECT, Manifest.permission.BLUETOOTH_SCAN)
    } else {
        listOf(Manifest.permission.ACCESS_FINE_LOCATION)
    }
}

private fun bluetoothPrintPermissions(): List<String> {
    return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
        listOf(Manifest.permission.BLUETOOTH_CONNECT)
    } else {
        emptyList()
    }
}

private fun parseColor(value: String): Color {
    return runCatching { Color(android.graphics.Color.parseColor(value)) }.getOrDefault(Color(0xFF2563EB))
}

private fun bestContentColor(background: Color): Color {
    val brightness = (background.red * 0.299f) + (background.green * 0.587f) + (background.blue * 0.114f)
    return if (brightness > 0.62f) Color(0xFF0F172A) else Color.White
}

private fun formatMoney(value: Number, currency: String): String {
    val normalizedCurrency = currency.ifBlank { "IDR" }.uppercase(Locale.US)
    val number = NumberFormat.getNumberInstance(Locale("id", "ID")).apply {
        maximumFractionDigits = 0
        minimumFractionDigits = 0
    }.format(value.toDouble())

    return if (normalizedCurrency == "IDR") "Rp$number" else "$normalizedCurrency $number"
}

private fun productMeta(item: PosItem, setting: StoreSetting): String {
    val parts = mutableListOf(itemTypeLabel(item.type))
    if (setting.showSku && item.sku != null) parts += item.sku
    if (setting.showStock) parts += item.stock?.let { "Stok ${formatQuantity(it)} ${item.unit}" } ?: "Tanpa stok"
    return parts.joinToString(" | ")
}

private fun catalogLineTotal(item: PosItem, quantity: Double): Double {
    return item.price.toDouble() * catalogChargedQuantity(item, quantity)
}

private fun catalogChargedQuantity(item: PosItem, quantity: Double): Double {
    val freeQuantity = item.freeQuantity ?: 0.0
    return if (freeQuantity > 0.0 && quantity <= freeQuantity) 0.0 else quantity
}

private fun checkoutRuleDescription(line: CheckoutLine): String? {
    val freeQuantity = line.freeQuantity?.takeIf { it > 0.0 } ?: return null
    return freeRuleDescription(freeQuantity, line.chargedQuantity, line.unit)
}

private fun freeRuleDescription(freeQuantity: Double, chargedQuantity: Double, unit: String): String {
    return if (chargedQuantity <= 0.0) {
        "Gratis sampai ${formatQuantity(freeQuantity)} $unit, ditagih 0 $unit"
    } else {
        "Gratis sampai ${formatQuantity(freeQuantity)} $unit, ditagih ${formatQuantity(chargedQuantity)} $unit"
    }
}

private fun itemTypeLabel(type: String): String {
    return when (type.lowercase(Locale.US)) {
        "product" -> "Produk"
        "service" -> "Layanan"
        "discount" -> "Diskon"
        "fee" -> "Biaya"
        "custom" -> "Fleksibel"
        else -> type
    }
}

private fun formatQuantity(value: Double): String {
    return if (value % 1.0 == 0.0) value.toInt().toString() else String.format(Locale.US, "%.3f", value).trimEnd('0').trimEnd('.')
}

private fun quantityStep(unit: String): Double {
    return when (unit.lowercase(Locale.US)) {
        "pcs" -> 1.0
        "trx" -> 1.0
        "ml" -> 5.0
        "gram" -> 100.0
        "kg" -> 0.1
        "meter" -> 0.1
        else -> 1.0
    }
}

private fun allowsFractionalUnit(unit: String): Boolean = unit.lowercase(Locale.US) !in listOf("pcs", "trx")

private fun normalizeQuantity(value: Double): Double = String.format(Locale.US, "%.3f", value).toDouble()

@Preview(showBackground = true)
@Composable
fun PosScreenPreview() {
    ArventaPOSTheme {
        PosScreen(PosState(setting = demoSetting, items = listOf(PosItem(1, "Kopi Susu", "SKU-1", "product", "pcs", 18000, 10.0, null, null))), PairingSession("http://10.0.2.2:8000", "preview", "Kasir"), "Kasir", {}, {})
    }
}
