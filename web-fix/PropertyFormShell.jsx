import React from 'react';

const PropertyFormShell = ({ title, tabItems, activeTab, onTabChange, children, showRoundedBorders = false }) => {
    return (
        <div className="space-y-4 p-2 md:p-0">
            <h1 className="text-2xl font-semibold">{title}</h1>

            <div className={`bg-white w-full ${showRoundedBorders ? 'rounded-xl md:rounded-2xl' : ''}`}>

                <div className="border-b">
                    <div className="flex overflow-x-auto">
                        {tabItems.map((tab) => (
                            <button
                                key={tab.id}
                                type="button"
                                onClick={() => onTabChange(tab.id)}
                                className={`relative px-6 py-3 rounded-none border-b-2 whitespace-nowrap ${activeTab === tab.id
                                    ? 'primaryBorderColor primaryColor'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                                    }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="p-6">{children}</div>
            </div>
        </div>
    );
};

export default PropertyFormShell;