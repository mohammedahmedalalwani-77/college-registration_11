<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;

class OfficerController extends Controller
{
    /**
     * عرض قائمة الطلبات مع معالجة البحث الدقيق والإحصائيات المرتبطة بقاعدة البيانات.
     */
    public function applications(Request $request, $status = 'all')
    {
        // بناء الاستعلام الأساسي مع العلاقات
        $query = Application::with(['user', 'major']);

        // 1. تصفية النتائج حسب الحالة إذا لم تكن 'all'
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // 2. البحث الدقيق واستثناء بقية الطلاب عند إدخال قيمة في البحث
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // تنفيذ التقسيم مع الاحتفاظ ببيانات البحث في الروابط
        $applications = $query->paginate(10)->withQueryString();

        // 3. حساب الإحصائيات الحقيقية المرتبطة بقاعدة البيانات مباشرة
        $approvedCount = Application::where('status', 'approved')->count();
        $rejectedCount = Application::where('status', 'rejected')->count();
        $totalCount = Application::count();

        // إرسال البيانات إلى واجهة العرض
        return view('officer.applications.index', compact(
            'applications', 
            'status', 
            'approvedCount', 
            'rejectedCount', 
            'totalCount'
        ));
    }
}