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
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
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
import androidx.compose.runtime.mutableStateListOf
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
    val qrisImageUrl: String?,
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
    val receiptFooter: String,
)

data class PosItem(
    val id: Int,
    val name: String,
    val sku: String?,
    val type: String,
    val unit: String,
    val price: Int,
    val stock: Double?,
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

private val demoSetting = StoreSetting(
    storeName = "Arventa POS",
    businessType = "Retail",
    qrisImageUrl = null,
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
    receiptFooter = "Terima kasih.",
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
                SessionStore.clear(context)
                session = null
            },
        )
    }
}

@Composable
private fun PairingScreen(onConnected: (PairingSession) -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var baseUrl by remember { mutableStateOf("https://arventa.arventa.my.id") }
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

    fun connect(input: String) {
        loading = true
        message = null
        scope.launch {
            try {
                onConnected(PosRepository.pair(baseUrl, input))
            } catch (error: Exception) {
                message = error.message ?: "Pairing gagal."
            } finally {
                loading = false
            }
        }
    }

    Surface(color = Color(0xFFF8FAFC), modifier = Modifier.fillMaxSize()) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(24.dp),
            verticalArrangement = Arrangement.Center,
        ) {
            Text("Arventa POS", style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold)
            Text(
                "Hubungkan app kasir dengan web admin memakai kode 6 digit atau payload QR dari menu Perangkat Kasir.",
                color = Color(0xFF64748B),
                modifier = Modifier.padding(top = 8.dp, bottom = 24.dp),
            )
            Card(colors = CardDefaults.cardColors(containerColor = Color.White), shape = RoundedCornerShape(16.dp)) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
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
                        value = baseUrl,
                        onValueChange = { baseUrl = it },
                        label = { Text("Base URL web admin") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    OutlinedTextField(
                        value = pairingInput,
                        onValueChange = { pairingInput = it },
                        label = { Text("Kode pairing atau isi QR") },
                        minLines = 2,
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
                    ) {
                        Text(if (scannerOpen) "Arahkan kamera ke QR" else "Scan QR Pairing")
                    }
                    Button(
                        onClick = {
                            connect(pairingInput)
                        },
                        enabled = !loading && pairingInput.isNotBlank(),
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        if (loading) {
                            CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp)
                            Spacer(Modifier.width(8.dp))
                        }
                        Text(if (loading) "Menghubungkan..." else "Hubungkan Perangkat")
                    }
                    message?.let {
                        Text(it, color = Color(0xFFDC2626), style = MaterialTheme.typography.bodySmall)
                    }
                    Text(
                        "Gunakan https://arventa.arventa.my.id untuk server production. Untuk backend lokal di emulator, pakai http://10.0.2.2:8000.",
                        color = Color(0xFF64748B),
                        style = MaterialTheme.typography.bodySmall,
                    )
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
    val customItems = remember { mutableStateListOf<PosItem>() }
    var nextCustomItemId by remember { mutableStateOf(-1) }
    val allItems = items + customItems
    val cart = remember { mutableStateMapOf<Int, Double>() }
    val subtotal = cart.entries.sumOf { (id, qty) -> ((allItems.firstOrNull { it.id == id }?.price ?: 0).toDouble()) * qty }
    val tax = subtotal * setting.taxRate / 100
    val service = subtotal * setting.serviceChargeRate / 100
    val total = subtotal + tax + service
    val configuration = LocalConfiguration.current
    val isWide = configuration.screenWidthDp >= 700 || setting.posOrientation == "landscape"
    val useSideCart = setting.showCart && isWide && setting.cartPosition == "right"
    var checkoutOpen by remember { mutableStateOf(false) }
    var checkoutLoading by remember { mutableStateOf(false) }
    var checkoutError by remember { mutableStateOf<String?>(null) }
    var receipt by remember { mutableStateOf<SaleReceipt?>(null) }

    val checkoutLines = cart.entries.mapNotNull { (id, qty) ->
        allItems.firstOrNull { it.id == id }?.let { item ->
            CheckoutLine(if (item.id > 0) item.id else null, item.name, item.unit, item.price, qty)
        }
    }
    val addCustomItem: (String, Int, String, Double) -> Unit = { name, price, unit, quantity ->
        val item = PosItem(
            id = nextCustomItemId--,
            name = name,
            sku = null,
            type = "custom",
            unit = unit,
            price = price,
            stock = null,
            imageUrl = null,
        )
        customItems.add(item)
        cart[item.id] = normalizeQuantity(quantity)
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
        topBar = { StoreHeader(setting, cashierName, onRefresh, onDisconnect) },
        bottomBar = {
            if (setting.checkoutPosition == "bottom" && !useSideCart) {
                CheckoutBar(setting, subtotal, tax, service, total, openCheckout)
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
                if (useSideCart) {
                    Row(Modifier.fillMaxSize().padding(16.dp), horizontalArrangement = Arrangement.spacedBy(14.dp)) {
                        ProductCatalog(setting, allItems, cart, addCustomItem, Modifier.weight(1f).fillMaxSize())
                        CartPanel(setting, allItems, cart, subtotal, tax, service, total, openCheckout, Modifier.width(260.dp))
                    }
                } else {
                    Column(Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        ProductCatalog(setting, allItems, cart, addCustomItem, Modifier.weight(1f))
                        if (setting.showCart) {
                            CartPanel(setting, allItems, cart, subtotal, tax, service, total, openCheckout, Modifier.fillMaxWidth())
                        }
                    }
                }
            }

            if (setting.checkoutPosition == "floating") {
                Button(
                    onClick = openCheckout,
                    enabled = total > 0,
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
}

@Composable
private fun StoreHeader(setting: StoreSetting, cashierName: String, onRefresh: () -> Unit, onDisconnect: () -> Unit) {
    Surface(color = setting.themeColor, shadowElevation = 2.dp) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 18.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(
                modifier = Modifier.size(34.dp).background(Color.White.copy(alpha = 0.18f), RoundedCornerShape(8.dp)),
                contentAlignment = Alignment.Center,
            ) { Text("A", color = Color.White, fontWeight = FontWeight.Bold) }
            Spacer(Modifier.width(12.dp))
            Column(Modifier.weight(1f)) {
                Text(setting.storeName, color = Color.White, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Text(setting.businessType, color = Color.White.copy(alpha = 0.82f), style = MaterialTheme.typography.bodySmall)
            }
            TextButton(
                onClick = onRefresh,
                colors = ButtonDefaults.textButtonColors(contentColor = Color.White),
            ) {
                Text("Sync")
            }
            Box(
                modifier = Modifier
                    .size(36.dp)
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
    onAddCustomItem: (String, Int, String, Double) -> Unit,
    modifier: Modifier = Modifier,
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
    var customDialogOpen by remember { mutableStateOf(false) }
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
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Text("Produk & Layanan", color = setting.textColor, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            OutlinedButton(onClick = { customDialogOpen = true }, shape = RoundedCornerShape(999.dp)) {
                Text("Item Custom")
            }
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
            EmptyCatalog(Modifier.fillMaxSize(), "Belum ada item", "Tambahkan produk dari web admin atau gunakan Item Custom.")
            if (customDialogOpen) {
                CustomItemDialog(setting, { customDialogOpen = false }) { name, price, unit, quantity ->
                    onAddCustomItem(name, price, unit, quantity)
                    customDialogOpen = false
                }
            }
            return
        }

        if (visibleItems.isEmpty()) {
            EmptyCatalog(Modifier.fillMaxSize(), "Item tidak ditemukan", "Coba kata kunci lain atau gunakan Item Custom.")
            if (customDialogOpen) {
                CustomItemDialog(setting, { customDialogOpen = false }) { name, price, unit, quantity ->
                    onAddCustomItem(name, price, unit, quantity)
                    customDialogOpen = false
                }
            }
            return
        }

        if (setting.appLayout == "grid") {
            LazyVerticalGrid(
                columns = GridCells.Adaptive(minSize = 168.dp),
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
        if (customDialogOpen) {
            CustomItemDialog(setting, { customDialogOpen = false }) { name, price, unit, quantity ->
                onAddCustomItem(name, price, unit, quantity)
                customDialogOpen = false
            }
        }
    }
}

@Composable
private fun CustomItemDialog(
    setting: StoreSetting,
    onDismiss: () -> Unit,
    onSave: (String, Int, String, Double) -> Unit,
) {
    var name by remember { mutableStateOf("") }
    var priceInput by remember { mutableStateOf("") }
    var unit by remember { mutableStateOf("pcs") }
    var quantityInput by remember { mutableStateOf("1") }
    var error by remember { mutableStateOf<String?>(null) }

    AlertDialog(
        onDismissRequest = onDismiss,
        containerColor = Color.White,
        titleContentColor = setting.textColor,
        textContentColor = setting.textColor,
        title = { Text("Item Custom", color = setting.textColor, fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = name,
                    onValueChange = {
                        name = it
                        error = null
                    },
                    label = { Text("Nama item") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(
                    value = priceInput,
                    onValueChange = {
                        priceInput = it.filter { char -> char.isDigit() }
                        error = null
                    },
                    label = { Text("Harga") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = unit,
                        onValueChange = {
                            unit = it.take(20)
                            error = null
                        },
                        label = { Text("Satuan") },
                        singleLine = true,
                        modifier = Modifier.weight(1f),
                    )
                    OutlinedTextField(
                        value = quantityInput,
                        onValueChange = {
                            quantityInput = it
                            error = null
                        },
                        label = { Text("Jumlah") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                        modifier = Modifier.weight(1f),
                    )
                }
                error?.let {
                    Text(it, color = Color(0xFFDC2626), style = MaterialTheme.typography.bodySmall)
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val price = priceInput.toIntOrNull()
                    val quantity = quantityInput.trim().replace(',', '.').toDoubleOrNull()
                    when {
                        name.trim().isBlank() -> error = "Nama item wajib diisi."
                        price == null || price <= 0 -> error = "Harga harus lebih dari 0."
                        unit.trim().isBlank() -> error = "Satuan wajib diisi."
                        quantity == null || quantity <= 0.0 -> error = "Jumlah harus lebih dari 0."
                        else -> onSave(name.trim(), price, unit.trim(), normalizeQuantity(quantity))
                    }
                },
                colors = ButtonDefaults.buttonColors(
                    containerColor = setting.themeColor,
                    contentColor = bestContentColor(setting.themeColor),
                ),
            ) {
                Text("Tambah")
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
                Text("${formatRupiah(item.price)} / ${item.unit}", color = setting.priceTextColor, fontWeight = FontWeight.Bold)
            }
            QuantityStepper(quantity, item.unit, item.stock, setting, onAdd, onRemove, onSetQuantity)
        }
    }
}

@Composable
private fun ProductTile(item: PosItem, quantity: Double, setting: StoreSetting, onAdd: () -> Unit, onRemove: () -> Unit, onSetQuantity: (Double) -> Unit) {
    Card(shape = RoundedCornerShape(10.dp), colors = CardDefaults.cardColors(containerColor = Color.White), modifier = Modifier.clickable(onClick = onAdd)) {
        Column(Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            if (setting.productCardStyle == "image") {
                ProductImage(item, setting, Modifier.fillMaxWidth().height(92.dp))
            }
            Text(item.name, color = setting.textColor, fontWeight = FontWeight.SemiBold, maxLines = 2, overflow = TextOverflow.Ellipsis)
            Text(productMeta(item, setting), color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
            Text("${formatRupiah(item.price)} / ${item.unit}", color = setting.priceTextColor, fontWeight = FontWeight.Bold)
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
    cart: Map<Int, Double>,
    subtotal: Double,
    tax: Double,
    service: Double,
    total: Double,
    onCheckout: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val selected = cart.entries.mapNotNull { (id, qty) -> items.firstOrNull { it.id == id }?.let { it to qty } }
    Card(modifier = modifier, shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Cart", color = setting.textColor, fontWeight = FontWeight.Bold)
                Text("${formatQuantity(selected.sumOf { it.second })} item", color = setting.secondaryTextColor)
            }
            if (selected.isEmpty()) {
                Text("Belum ada item dipilih", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
            } else {
                selected.take(3).forEach { (item, qty) ->
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("${formatQuantity(qty)} ${item.unit} x ${item.name}", color = setting.secondaryTextColor, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f))
                        Text(formatRupiah(item.price * qty), color = setting.priceTextColor, fontWeight = FontWeight.SemiBold)
                    }
                }
            }
            if (setting.showOrderSummary) {
                SummaryLine("Subtotal", subtotal, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Pajak", tax, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Service", service, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Total", total, setting.textColor, setting.priceTextColor, true)
            }
            if (setting.checkoutPosition == "cart") {
                CheckoutActionButton(setting, total, onCheckout, Modifier.fillMaxWidth(), "Checkout")
            }
        }
    }
}

@Composable
private fun CheckoutBar(setting: StoreSetting, subtotal: Double, tax: Double, service: Double, total: Double, onCheckout: () -> Unit) {
    Surface(color = Color.White, shadowElevation = 8.dp) {
        Column(Modifier.fillMaxWidth().padding(16.dp)) {
            if (setting.showOrderSummary) {
                SummaryLine("Subtotal", subtotal, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Pajak", tax, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Service", service, setting.secondaryTextColor, setting.priceTextColor)
                Spacer(Modifier.height(8.dp))
            }
            SummaryLine("Total", total, setting.textColor, setting.priceTextColor, true)
            Spacer(Modifier.height(10.dp))
            CheckoutActionButton(setting, total, onCheckout, Modifier.fillMaxWidth(), if (setting.showCart) "Checkout" else "Bayar")
            Text(setting.receiptFooter, modifier = Modifier.fillMaxWidth().padding(top = 8.dp), color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
        }
    }
}

@Composable
private fun CheckoutActionButton(setting: StoreSetting, total: Double, onCheckout: () -> Unit, modifier: Modifier = Modifier, label: String = "Checkout") {
    Button(
        onClick = onCheckout,
        enabled = total > 0.0,
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
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text("${formatQuantity(line.quantity)} ${line.unit} x ${line.name}", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall, modifier = Modifier.weight(1f))
                                Text(formatRupiah(line.unitPrice * line.quantity), color = setting.priceTextColor, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
                            }
                        }
                        SummaryLine("Subtotal", subtotal, setting.secondaryTextColor, setting.priceTextColor)
                        SummaryLine("Pajak", tax, setting.secondaryTextColor, setting.priceTextColor)
                        SummaryLine("Service", service, setting.secondaryTextColor, setting.priceTextColor)
                        SummaryLine("Total", total, setting.textColor, setting.priceTextColor, true)
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
                        Text("Kembalian ${formatRupiah(change.coerceAtLeast(0.0))}", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 6.dp))
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
    var printerPickerOpen by remember { mutableStateOf(false) }
    var printers by remember { mutableStateOf<List<PrinterDevice>>(emptyList()) }
    var scanning by remember { mutableStateOf(false) }
    var printing by remember { mutableStateOf(false) }
    var scanReceiver by remember { mutableStateOf<BroadcastReceiver?>(null) }

    fun stopScan() {
        runCatching {
            BluetoothReceiptPrinter.stopScan(context)
        }
        scanReceiver?.let { receiver ->
            runCatching { context.unregisterReceiver(receiver) }
        }
        scanReceiver = null
        scanning = false
    }

    fun openPrinterPicker() {
        runCatching {
            printers = BluetoothReceiptPrinter.pairedPrinters(context)
            printerPickerOpen = true
            printMessage = if (printers.isEmpty()) "Belum ada printer paired. Tekan Cari Printer." else null
        }.onFailure {
            printMessage = it.message ?: "Gagal membaca daftar printer."
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
                    printMessage = null
                },
                onFinished = {
                    scanning = false
                    printMessage = if (printers.isEmpty()) "Printer belum ditemukan. Pastikan printer menyala dan mode discoverable." else null
                },
            )
            scanReceiver = receiver
            scanning = true
            printMessage = "Mencari printer Bluetooth..."
        }.onFailure {
            scanning = false
            printMessage = it.message ?: "Gagal scan printer."
        }
    }

    val bluetoothPermissionLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { grants ->
        val granted = bluetoothRuntimePermissions().all { grants[it] == true || ContextCompat.checkSelfPermission(context, it) == PackageManager.PERMISSION_GRANTED }
        if (granted) {
            openPrinterPicker()
        } else {
            printMessage = "Izin Bluetooth/Location dibutuhkan untuk scan dan cetak printer."
        }
    }

    fun requestPrint() {
        val missing = bluetoothRuntimePermissions()
            .filter { ContextCompat.checkSelfPermission(context, it) != PackageManager.PERMISSION_GRANTED }

        if (missing.isNotEmpty()) {
            bluetoothPermissionLauncher.launch(missing.toTypedArray())
        } else {
            openPrinterPicker()
        }
    }

    fun printTo(device: PrinterDevice) {
        stopScan()
        printerPickerOpen = false
        printing = true
        printMessage = "Mengirim struk ke ${device.name}..."
        scope.launch {
            try {
                BluetoothReceiptPrinter.print(context, device.address, setting, sale)
                printMessage = "Struk terkirim ke ${device.name}."
            } catch (error: Exception) {
                printMessage = error.message ?: "Gagal mencetak struk."
            } finally {
                printing = false
            }
        }
    }

    DisposableEffect(Unit) {
        onDispose { stopScan() }
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
                        Text(formatRupiah(item.lineTotal), color = setting.priceTextColor, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
                    }
                }
                SummaryLine("Total", sale.grandTotal, setting.textColor, setting.priceTextColor, true)
                SummaryLine("Dibayar (${sale.paymentMethod.uppercase(Locale.US)})", sale.paidAmount, setting.secondaryTextColor, setting.priceTextColor)
                SummaryLine("Kembalian", sale.changeAmount, setting.secondaryTextColor, setting.priceTextColor)
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

    if (printerPickerOpen) {
        AlertDialog(
            onDismissRequest = { if (!printing) printerPickerOpen = false },
            containerColor = Color.White,
            titleContentColor = setting.textColor,
            textContentColor = setting.textColor,
            title = { Text("Pilih Printer", color = setting.textColor, fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Button(
                        onClick = { startScan() },
                        enabled = !printing && !scanning,
                        colors = ButtonDefaults.buttonColors(containerColor = setting.themeColor, contentColor = bestContentColor(setting.themeColor)),
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        if (scanning) {
                            CircularProgressIndicator(modifier = Modifier.size(16.dp), strokeWidth = 2.dp, color = bestContentColor(setting.themeColor))
                            Spacer(Modifier.width(8.dp))
                        }
                        Text(if (scanning) "Mencari..." else "Cari Printer")
                    }
                    if (printers.isEmpty()) {
                        Text("Belum ada printer. Nyalakan printer lalu tekan Cari Printer.", color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
                    } else {
                        LazyColumn(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            items(printers) { device ->
                                Card(
                                    colors = CardDefaults.cardColors(containerColor = Color(0xFFF8FAFC)),
                                    modifier = Modifier.fillMaxWidth().clickable(enabled = !printing) { printTo(device) },
                                    shape = RoundedCornerShape(12.dp),
                                ) {
                                    Column(Modifier.padding(12.dp)) {
                                        Text(device.name, color = setting.textColor, fontWeight = FontWeight.SemiBold)
                                        Text(if (device.bonded) "Paired" else "Ditemukan", color = setting.themeColor, style = MaterialTheme.typography.bodySmall)
                                        Text(device.address, color = setting.secondaryTextColor, style = MaterialTheme.typography.bodySmall)
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
                        printerPickerOpen = false
                    },
                    enabled = !printing,
                    colors = ButtonDefaults.textButtonColors(contentColor = setting.secondaryTextColor),
                ) {
                    Text("Batal")
                }
            },
        )
    }
}

@Composable
private fun SummaryLine(label: String, value: Number, labelColor: Color, valueColor: Color, bold: Boolean = false) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, color = labelColor, style = MaterialTheme.typography.bodySmall)
        Text(formatRupiah(value), color = valueColor, fontWeight = if (bold) FontWeight.Bold else FontWeight.Normal)
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
        PairingSession(
            baseUrl = parsed.second,
            token = json.getString("token"),
            cashierName = cashier?.optString("name")?.takeIf { it.isNotBlank() } ?: "Kasir",
        )
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
            val code = json.getString("code")
            val apiUrl = json.optString("api_url")
            val baseUrl = apiUrl
                .replace("/api/pairing/connect", "")
                .ifBlank { baseUrlInput }
            return code to normalizeBaseUrl(baseUrl)
        }
        return trimmed.filter { it.isDigit() } to normalizeBaseUrl(baseUrlInput)
    }

    private fun JSONObject.toSetting(baseUrl: String): StoreSetting {
        return StoreSetting(
            storeName = optString("store_name", "Arventa POS"),
            businessType = optString("business_type", "Retail"),
            qrisImageUrl = optString("qris_image_url").takeIf { it.isNotBlank() && it != "null" }?.let { absoluteUrl(baseUrl, it) },
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
            receiptFooter = optString("receipt_footer", "Terima kasih."),
        )
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
        fun command(vararg bytes: Int) {
            buffer.write(bytes.map { it.toByte() }.toByteArray())
        }
        fun text(value: String = "") {
            buffer.write(sanitizeReceiptText(value).toByteArray(charset))
            buffer.write('\n'.code)
        }

        command(0x1B, 0x40)
        command(0x1B, 0x61, 0x01)
        command(0x1B, 0x21, 0x08)
        text(setting.storeName)
        command(0x1B, 0x21, 0x00)
        text(setting.businessType)
        text(line())
        command(0x1B, 0x61, 0x00)
        text("Invoice: ${sale.invoiceNumber}")
        text("Pembayaran: ${sale.paymentMethod.uppercase(Locale.US)}")
        text(line())

        sale.items.forEach { item ->
            wrapReceiptLine("${formatQuantity(item.quantity)} ${item.unit} x ${item.name}").forEach(::text)
            text(twoColumn("", formatRupiah(item.lineTotal)))
        }

        text(line())
        text(twoColumn("Subtotal", formatRupiah(sale.subtotal)))
        if (sale.taxTotal > 0.0) text(twoColumn("Pajak", formatRupiah(sale.taxTotal)))
        if (sale.serviceTotal > 0.0) text(twoColumn("Service", formatRupiah(sale.serviceTotal)))
        text(twoColumn("Total", formatRupiah(sale.grandTotal)))
        text(twoColumn("Dibayar", formatRupiah(sale.paidAmount)))
        text(twoColumn("Kembali", formatRupiah(sale.changeAmount)))
        text(line())
        command(0x1B, 0x61, 0x01)
        wrapReceiptLine(setting.receiptFooter.ifBlank { "Terima kasih." }).forEach(::text)
        text()
        text()
        command(0x1D, 0x56, 0x42, 0x00)

        return buffer.toByteArray()
    }

    private fun sanitizeReceiptText(value: String): String {
        return value
            .replace("–", "-")
            .replace("—", "-")
            .replace("’", "'")
            .replace("“", "\"")
            .replace("”", "\"")
    }

    private fun line(): String = "-".repeat(32)

    private fun twoColumn(left: String, right: String): String {
        val cleanLeft = sanitizeReceiptText(left).take(32)
        val cleanRight = sanitizeReceiptText(right).take(32)
        val spaces = (32 - cleanLeft.length - cleanRight.length).coerceAtLeast(1)
        return cleanLeft + " ".repeat(spaces) + cleanRight
    }

    private fun wrapReceiptLine(value: String): List<String> {
        val words = sanitizeReceiptText(value).split(" ")
        val lines = mutableListOf<String>()
        var current = ""

        words.forEach { word ->
            current = when {
                current.isBlank() -> word.take(32)
                current.length + 1 + word.length <= 32 -> "$current $word"
                else -> {
                    lines += current
                    word.take(32)
                }
            }
        }

        if (current.isNotBlank()) lines += current
        return lines.ifEmpty { listOf(value.take(32)) }
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

private fun parseColor(value: String): Color {
    return runCatching { Color(android.graphics.Color.parseColor(value)) }.getOrDefault(Color(0xFF2563EB))
}

private fun bestContentColor(background: Color): Color {
    val brightness = (background.red * 0.299f) + (background.green * 0.587f) + (background.blue * 0.114f)
    return if (brightness > 0.62f) Color(0xFF0F172A) else Color.White
}

private fun formatRupiah(value: Number): String {
    return NumberFormat.getCurrencyInstance(Locale.forLanguageTag("id-ID")).format(value.toDouble()).replace(",00", "")
}

private fun productMeta(item: PosItem, setting: StoreSetting): String {
    val parts = mutableListOf(item.type)
    if (setting.showSku && item.sku != null) parts += item.sku
    if (setting.showStock) parts += item.stock?.let { "Stok ${formatQuantity(it)} ${item.unit}" } ?: "Tanpa stok"
    return parts.joinToString(" | ")
}

private fun formatQuantity(value: Double): String {
    return if (value % 1.0 == 0.0) value.toInt().toString() else String.format(Locale.US, "%.3f", value).trimEnd('0').trimEnd('.')
}

private fun quantityStep(unit: String): Double {
    return when (unit.lowercase(Locale.US)) {
        "pcs" -> 1.0
        "ml" -> 50.0
        "gram" -> 100.0
        "kg" -> 0.1
        "meter" -> 0.1
        else -> 1.0
    }
}

private fun allowsFractionalUnit(unit: String): Boolean = unit.lowercase(Locale.US) != "pcs"

private fun normalizeQuantity(value: Double): Double = String.format(Locale.US, "%.3f", value).toDouble()

@Preview(showBackground = true)
@Composable
fun PosScreenPreview() {
    ArventaPOSTheme {
        PosScreen(PosState(setting = demoSetting, items = listOf(PosItem(1, "Kopi Susu", "SKU-1", "product", "pcs", 18000, 10.0, null))), PairingSession("http://10.0.2.2:8000", "preview", "Kasir"), "Kasir", {}, {})
    }
}
