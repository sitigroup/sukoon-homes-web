import 'package:ebroker/data/model/trust_verification_models.dart';
import 'package:ebroker/data/repositories/trust_verification_repository.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/utils/payment/payment_webview_screen.dart';
import 'package:flutter/material.dart';

/// Native order status and payment for Trust Verification.
class TrustVerificationOrderDetailScreen extends StatefulWidget {
  const TrustVerificationOrderDetailScreen({
    required this.orderId,
    super.key,
  });

  final int orderId;

  static Route<dynamic> route({required int orderId}) {
    return MaterialPageRoute<void>(
      builder: (_) => TrustVerificationOrderDetailScreen(orderId: orderId),
    );
  }

  @override
  State<TrustVerificationOrderDetailScreen> createState() =>
      _TrustVerificationOrderDetailScreenState();
}

class _TrustVerificationOrderDetailScreenState
    extends State<TrustVerificationOrderDetailScreen> {
  final TrustVerificationRepository _repository = TrustVerificationRepository();
  bool _loading = true;
  String? _error;
  TrustVerificationOrderDetail? _order;
  bool _paying = false;

  @override
  void initState() {
    super.initState();
    unawaited(_load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final order = await _repository.fetchOrderDetail(widget.orderId);
      if (!mounted) return;
      setState(() {
        _order = order;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _pay() async {
    setState(() => _paying = true);
    try {
      final url = await _repository.createPaymentUrl(widget.orderId);
      if (!mounted) return;
      final paid = await Navigator.push<bool>(
        context,
        MaterialPageRoute<bool>(
          builder: (context) => PaymentWebViewScreen(
            url: url,
            gateway: 'cashfree',
          ),
        ),
      );
      if (paid == true && mounted) {
        HelperUtils.showSnackBarMessage(
          context,
          'Payment completed. Refreshing order…',
        );
        await _load();
      }
    } catch (e) {
      if (mounted) {
        HelperUtils.showSnackBarMessage(context, e.toString());
      }
    } finally {
      if (mounted) setState(() => _paying = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.color.backgroundColor,
      appBar: CustomAppBar(title: 'Verification order'),
      body: _loading
          ? Center(child: UiUtils.progress())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_error!, textAlign: TextAlign.center),
                        const SizedBox(height: 16),
                        UiUtils.buildButton(
                          context,
                          onPressed: _load,
                          height: 44,
                          buttonTitle: 'Retry',
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _infoCard(context),
                      if (_order!.needsPayment) ...[
                        const SizedBox(height: 16),
                        UiUtils.buildButton(
                          context,
                          onPressed: _paying ? () {} : _pay,
                          height: 48,
                          buttonTitle:
                              _paying ? 'Opening payment…' : 'Pay now',
                        ),
                      ],
                      const SizedBox(height: 24),
                      Text(
                        'Order updates appear here after payment and document review.',
                        style: TextStyle(
                          color: context.color.textLightColor,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _infoCard(BuildContext context) {
    final order = _order!;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: context.color.secondaryColor,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: context.color.borderColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            order.packageName ?? 'Verification order',
            style: TextStyle(
              fontSize: context.font.lg,
              fontWeight: FontWeight.w700,
              color: context.color.textColorDark,
            ),
          ),
          if (order.orderNumber != null) ...[
            const SizedBox(height: 4),
            Text(
              '#${order.orderNumber}',
              style: TextStyle(color: context.color.textLightColor),
            ),
          ],
          const SizedBox(height: 12),
          _row('Status', order.status ?? '—'),
          _row('Payment', order.paymentStatus ?? '—'),
          if (order.amount != null)
            _row('Amount', '₹${order.amount!.toStringAsFixed(0)}'),
          if (order.subjectName != null)
            _row('Subject', order.subjectName!),
          if (order.subjectPhone != null)
            _row('Phone', order.subjectPhone!),
        ],
      ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 88,
            child: Text(
              label,
              style: TextStyle(
                color: context.color.textLightColor,
                fontSize: context.font.sm,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                color: context.color.textColorDark,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
